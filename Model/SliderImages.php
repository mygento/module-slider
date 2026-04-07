<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Model;

use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Mygento\Slider\Api\Data\BannerInterface;
use Mygento\Slider\Api\Data\SliderInterface;
use Mygento\Slider\Model\ResourceModel\Banner;

class SliderImages
{
    public function __construct(
        private Resizer $service,
        private Banner\CollectionFactory $factory,
        private DateTime $date,
        private ImageFactory $imageFactory,
        private StoreManagerInterface $storeManager,
    ) {}

    public function getImages(SliderInterface $slider): array
    {
        $options = $slider->getOptionsList();
        $jpg = isset($options['jpg']) && $options['jpg'] === true;
        $width = $this->getIntegerOption($options, 'width');
        $height = $this->getIntegerOption($options, 'height');

        $currentDate = $this->date->gmtDate();

        /** @var Banner\Collection $collection */
        $collection = $this->factory->create();
        $collection->addFieldToFilter(BannerInterface::IS_ACTIVE, 1);
        $collection->fetchItemsWithPositionBySlider((int) $slider->getId());
        $collection->addFilter(
            BannerInterface::STORE_ID,
            ['in' => $this->storeManager->getStore()->getId()],
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
            $item['formats'] = $this->buildImageFormats($options, $item['image'] ?? null, $item['small_image'] ?? null);
            $item['default'] = $this->service->resizeAndConvert($item['image'] ?? null, $jpg ? 'jpg' : null, $width, $height);
            $result[] = $item;
        }

        return $result;
    }

    public function getIntegerOption(array $options, string $key): ?int
    {
        return isset($options[$key]) && $options[$key] !== null ? (int) $options[$key] : null;
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

    public function getImageData(Product $product, array $options): array
    {
        $img = $product->getData('thumbnail');
        $jpg = isset($options['jpg']) && $options['jpg'] === true;
        $sizes = $this->getSizes($product);
        $width = $sizes['width'] ?? null;
        $height = $sizes['height'] ?? null;

        try {
            return [
                'formats' => $this->buildImageFormats($options, [
                    'image' => [
                        'path' => $img ?? null,
                        'width' => $width,
                        'height' => $height,
                    ],
                ]),
                'default' => $this->service->resizeAndConvert($img ?? null, $jpg ? 'jpg' : null, $width, $height),
            ];
        } catch (LocalizedException) {
            return [
                'formats' => [],
                'default' => '',
            ];
        }
    }

    public function getSizes(Product $product): array
    {
        /** @var Image $image */
        $image = $this->imageFactory->create($product, 'category_page_grid');

        $width = $image->getWidth();
        $height = $image->getHeight();

        return [
            'width' => $width,
            'height' => $height,
        ];
    }

    public function buildImageFormats(array $options, array $images = []): array
    {
        if (empty($images)) {
            return [];
        }

        $result = [];

        foreach (['avif', 'webp', 'jpg'] as $ext) {
            if (!isset($options[$ext]) || $options[$ext] !== true) {
                continue;
            }

            foreach ($images as $key => $config) {
                $imagePath = $config['path'] ?? null;
                $width = $config['width'] ?? null;
                $height = $config['height'] ?? null;

                if ($imagePath === null) {
                    continue;
                }

                $result[$ext][$key] = $this->resizeImage($ext, $imagePath, $width, $height);
            }
        }

        return $result;
    }

    private function resizeImage(?string $ext = null, ?string $image = null, ?int $width = null, ?int $height = null): array
    {
        $result = [];
        for ($i = 1;$i <= 3;$i++) {
            try {
                $file = $this->service->resizeAndConvert(
                    $image,
                    $ext,
                    $width !== null ? $width * $i : null,
                    $height !== null ? $height * $i : null,
                );
                if ($file === null) {
                    continue;
                }
                $result[($width * $i) . 'w'] = $file;
            } catch (LocalizedException) {
                continue;
            }
        }

        return $result;
    }
}
