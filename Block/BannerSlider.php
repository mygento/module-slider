<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Block;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Mygento\Slider\Api\Data\BannerInterface;
use Mygento\Slider\Api\Data\SliderInterface;
use Mygento\Slider\Model\Resizer;
use Mygento\Slider\Model\ResourceModel\Banner;
use Mygento\Slider\Model\ResourceModel\Slider;
use Mygento\Slider\Model\SliderOptions;

class BannerSlider extends Template implements BlockInterface
{
    protected $_template = 'Mygento_Slider::banner_slider.phtml';

    public function __construct(
        private Resizer $service,
        private Slider\CollectionFactory $sliderCollectionFactory,
        private SliderOptions $sliderImages,
        private Banner\CollectionFactory $factory,
        private DateTime $date,
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

    public function createSrcSet(array $items): string
    {
        $images = array_reduce(
            array_keys($items),
            fn($carry, $key) => [...$carry, $items[$key] . ' ' . $key],
            [],
        );

        return implode(', ', $images);
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
        $options = $this->getSlider()->getOptionsList();
        $jpg = isset($options['jpg']) && $options['jpg'] === true;
        $width = $this->getIntegerOption($options, 'width');
        $height = $this->getIntegerOption($options, 'height');

        $currentDate = $this->date->gmtDate();

        /** @var Banner\Collection $collection */
        $collection = $this->factory->create();
        $collection->addFieldToFilter(BannerInterface::IS_ACTIVE, 1);
        $collection->fetchItemsWithPositionBySlider((int) $this->getSlider()->getId());
        $collection->addFilter(
            BannerInterface::STORE_ID,
            ['in' => $this->_storeManager->getStore()->getId()],
        );
        $collection->addFieldToFilter(BannerInterface::FROM_DATE, [
            ['null' => true],
            ['lteq' => $currentDate],
        ]);
        $collection->addFieldToFilter(BannerInterface::TO_DATE, [
            ['null' => true],
            ['gteq' => $currentDate],
        ]);

        $result = [];
        foreach ($collection as $entity) {
            $item = $entity->getData();
            $item['formats'] = $this->sliderImages->buildImageFormats($options, [
                'image' => [
                    'path' => $item['image'] ?? null,
                    'width' => $this->getIntegerOption($options, 'width'),
                    'height' => $this->getIntegerOption($options, 'height'),
                ],
                'small_image' => [
                    'path' => $item['small_image'] ?? null,
                    'width' => $this->getIntegerOption($options, 'width_small'),
                    'height' => $this->getIntegerOption($options, 'height_small'),
                ],
            ]);
            $item['default'] = $this->service->resizeAndConvert($item['image'] ?? null, $jpg ? 'jpg' : null, $width, $height);
            $result[] = $item;
        }

        return $result;
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

        foreach ($item['formats'] as $im) {
            $hasSmall = isset($im['small_image']) && !empty($im['small_image']);
            $mq = $hasSmall ? '(min-width: ' . $breakpoint . 'px)' : '';
            $mqS = $hasSmall ? '(max-width: ' . ($breakpoint - 1) . 'px)' : '';
            $this->pageConfig->addRemotePageAsset(
                $item['default'],
                'link_rel',
                [
                    'attributes' => [
                        'rel' => 'preload',
                        'as' => 'image',
                        'media' => $mq,
                        'imagesrcset' => $this->createSrcSet($im['image']),
                    ],
                ],
            );
            $this->pageConfig->addRemotePageAsset(
                $item['default'],
                'link_rel',
                [
                    'attributes' => [
                        'rel' => 'preload',
                        'as' => 'image',
                        'media' => $mqS,
                        'imagesrcset' => $this->createSrcSet($im['small_image']),
                    ],
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
