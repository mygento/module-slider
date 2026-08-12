<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Mygento\ImageCommon\Model\Resizer;
use Mygento\Slider\Api\Data\BannerInterface;
use Mygento\Slider\Api\Data\SliderInterface;
use Mygento\Slider\Model\Builder\LinkResolver;
use Mygento\Slider\Model\ResourceModel\Banner;
use Mygento\Slider\Model\ResourceModel\Slider;

class BannerSlider extends Template implements BlockInterface
{
    protected $_template = 'Mygento_Slider::banner_slider.phtml';

    public function __construct(
        private Resizer $service,
        private Slider\CollectionFactory $sliderCollectionFactory,
        private Banner\CollectionFactory $factory,
        private DateTime $date,
        private LinkResolver $linkResolver,
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
            if ($item['image'] === null) {
                continue;
            }

            try {
                $item['formats'] = $this->buildImageFormats($options, $item['image'], $item['small_image'] ?? null);
                $defaultImg = $this->service->execute(
                    imagePath: $item['image'],
                    width: $width,
                    height: $height,
                    ext: $jpg ? 'jpg' : null,
                );
                $item['default'] = $defaultImg['url'];
                $result[] = $item;
            } catch (LocalizedException) {
                continue;
            }
        }

        return $this->linkResolver->addLinks(
            $result,
            (int) $this->_storeManager->getStore()->getId(),
        );
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

    private function buildImageFormats(array $options, string $image, ?string $smallImage = null): array
    {
        $width = $this->getIntegerOption($options, 'width');
        $height = $this->getIntegerOption($options, 'height');
        $widthS = $this->getIntegerOption($options, 'width_small');
        $heightS = $this->getIntegerOption($options, 'height_small');
        $result = [];

        foreach (['avif', 'webp', 'jpg'] as $ext) {
            if (!isset($options[$ext]) || $options[$ext] !== true) {
                continue;
            }
            $result[$ext]['image'] = $this->service->execute(
                imagePath: $image,
                width: $width,
                height: $height,
                ext: $ext,
            );

            if ($smallImage === null || $widthS === null) {
                continue;
            }
            $result[$ext]['small_image'] = $this->service->execute(
                imagePath: $smallImage,
                width: $widthS,
                height: $heightS,
                ext: $ext,
            );
        }

        return $result;
    }
}
