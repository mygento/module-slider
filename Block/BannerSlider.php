<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Block;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Mygento\Slider\Api\Data\SliderInterface;
use Mygento\Slider\Model\DataBuilder\BannerBuilder;
use Mygento\Slider\Model\ResourceModel\Slider;

class BannerSlider extends Template implements BlockInterface
{
    protected $_template = 'Mygento_Slider::banner_slider.phtml';

    public function __construct(
        private BannerBuilder $builder,
        private Slider\CollectionFactory $sliderCollectionFactory,
        Template\Context $context,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function getCacheKeyInfo(): array
    {
        $cacheKeyInfo = parent::getCacheKeyInfo();
        $cacheKeyInfo[] = $this->getSliderIdentifier();

        return $cacheKeyInfo;
    }

    public function getTitle(): string
    {
        return $this->getSlider()?->getTitle() ?? $this->getSliderIdentifier();
    }

    public function getOptions(): array
    {
        return $this->getSlider()?->getOptionsList() ?? [];
    }

    public function getImages(): array
    {
        return $this->builder->getImages($this->getSlider());
    }

    public function getSliderIdentifier(): string
    {
        return 'banner_slider' . $this->getData('banner_slider');
    }

    public function getIntegerOption(array $options, string $key): ?int
    {
        return isset($options[$key]) && $options[$key] !== null ? (int) $options[$key] : null;
    }

    public function appendPreload(): void
    {
        if (!$this->getData('banner_slider')) {
            return;
        }

        $options = $this->getOptions();
        $preload = isset($options['preload']) && $options['preload'] === true;
        $breakpoint = $options['breakpoint'] ?? 1024;
        if (!$preload) {
            return;
        }
        $images = $this->getImages();
        if (count($images) === 0) {
            return;
        }

        $item = array_values($images)[0];

        foreach ($item['image_formats'] as $im) {
            $hasSmall = isset($im['small_image']) && !empty($im['small_image']);
            $attr = [
                'rel' => 'preload',
                'as' => 'image',
                'imagesrcset' => $im['image']['srcset'],
            ];
            if ($hasSmall) {
                $attr['media'] = '(min-width: ' . $breakpoint . 'px)';
            }
            $this->pageConfig->addRemotePageAsset(
                $item['default'],
                'link_rel',
                [
                    'attributes' => $attr,
                ],
            );
            if (!$hasSmall) {
                break;
            }
            $attr['media'] = '(max-width: ' . ($breakpoint - 1) . 'px)';
            $attr['imagesrcset'] = $im['small_image']['srcset'];

            $this->pageConfig->addRemotePageAsset(
                $item['default'],
                'link_rel',
                [
                    'attributes' => $attr,
                ],
            );
            break;
        }
    }

    protected function _toHtml(): string
    {
        if (!$this->getData('banner_slider')) {
            return '';
        }
        $slider = $this->getSlider();
        if (!$slider || !$slider->isActive()) {
            return '';
        }

        return parent::_toHtml();
    }

    private function getSlider(): ?SliderInterface
    {
        /** @var Slider\Collection $collection */
        $collection = $this->sliderCollectionFactory->create();
        $collection->addFieldToFilter(SliderInterface::IDENTITY, $this->getData('banner_slider'));
        $entity = $collection->getFirstItem();

        if (!$entity->getId()) {
            return null;
        }

        return $entity;
    }
}
