<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mygento\Slider\Api\Data\BannerInterface;
use Mygento\Slider\Api\SliderRepositoryInterface;
use Mygento\Slider\Model\ResourceModel\Banner\Collection;
use Mygento\Slider\Model\ResourceModel\Banner\CollectionFactory;
use Mygento\Slider\Model\SliderOptions;

class Slider implements ResolverInterface
{
    public function __construct(
        private SliderRepositoryInterface $sliderRepository,
        private SliderOptions $sliderOptions,
        private CollectionFactory $bannerCollectionFactory,
    ) {}

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ) {
        $identity = $args['identity'] ?? null;

        if (!$identity) {
            throw new GraphQlNoSuchEntityException(__('Slider Identity arg is required'));
        }

        try {
            $slider = $this->sliderRepository->getByIdentity($identity);
        } catch (\Exception) {
            throw new GraphQlNoSuchEntityException(__('Slider not found or disabled '));
        }
        if (!$slider->isActive()) {
            throw new GraphQlNoSuchEntityException(__('Slider not found or disabled '));
        }
        $bannerItems = [];
        if ($slider->getBannerIds()) {
            $bannerItems = $this->prepareBanners($slider->getBannerIds(), $this->sliderOptions->getOptions($slider->getOptions()));
        }

        return [
            'title' => $slider->getTitle(),
            'identity' => $slider->getIdentity(),
            'options' => $this->sliderOptions->getOptions($slider->getOptions()),
            'content' => $slider->getContent(),
            'is_active' => $slider->getIsActive(),
            'banners' => $bannerItems,
        ];
    }

    public function getBanners(array $bannerIds): array
    {
        /** @var Collection $collection */
        $collection = $this->bannerCollectionFactory->create();
        $collection->addFieldToFilter('id', ['in' => array_keys($bannerIds)]);
        $collection->addFieldToFilter('is_active', ['eq' => 1]);
        $collection->addFieldToSelect(['image', 'link', 'name', 'small_image']);

        return $collection->getItems();
    }

    private function prepareBanners(array $bannerIds, array $options = []): array
    {
        $bannerEntities = $this->getBanners($bannerIds);
        if (
            empty($options['breakpoint'])
            || empty($options['width'])
        ) {
            return [];
        }
        $bannerItems = [];
        foreach ($bannerEntities as $data) {
            /** @var BannerInterface $bannerItem */
            $bannerItem = $data;
            $bannerItem['position'] = $data['position'];
            $bannerItem['image_formats'] = $this->prepareImages($bannerItem, $options);
            $bannerItems[] = $bannerItem;
        }

        return $bannerItems;
    }

    private function prepareImages(BannerInterface $banner, array $options = []): array
    {
        $image = $banner->getImage();
        if (!$image) {
            return [];
        }
        $images[] = [
            'path' => $image,
            'width' => (int) $options['width'],
            'height' => (int) $options['height'],
        ];
        if (
            !empty($banner->getSmallImage())
            && !empty($options['width_small'])
            && !empty($options['height_small'])
        ) {
            $images[] = [
                'path' => $banner->getSmallImage(),
                'width' => (int) $options['width_small'],
                'height' => (int) $options['height_small'],
            ];
        }
        $imagList = $this->sliderOptions->buildImageFormats($options, $images);

        return $this->getImageFormats($imagList);
    }

    private function getImageFormats(array $imagList): array
    {
        $typeMapping = [
            0 => 'image',
            1 => 'small_image',
        ];
        $result = [];
        foreach ($imagList as $format => $images) {
            if (!is_array($images)) {
                continue;
            }

            $result['formats'][$format] = [];
            foreach ($images as $index => $sizes) {
                if (!isset($typeMapping[$index]) || !is_array($sizes)) {
                    continue;
                }

                $type = $typeMapping[$index];
                $result['formats'][$format][$type] = [];

                foreach ($sizes as $size => $link) {
                    $result['formats'][$format][$type][] = [
                        'size' => $size,
                        'link' => $link,
                    ];
                }
            }
        }

        return $result;
    }
}
