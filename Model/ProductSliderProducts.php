<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\LocalizedException;
use Magento\Rule\Model\Condition\Sql\Builder;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Model\Catalog\Sorting;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductSliderProducts
{
    public function __construct(
        private Sorting $sorting,
        private SliderOptions $sliderOptions,
        private Config $catalogConfig,
        private Stock $stock,
        private CollectionFactory $productCollectionFactory,
        private Builder $sqlBuilder,
        private Resizer $service,
        private ImageFactory $imageFactory,
        private Visibility $catalogProductVisibility,
    ) {}

    public function getProductCollection(ProductSliderInterface $slider): Collection
    {
        $options = $slider->getOptions();
        $maxCount = $options['products_count'] ?? 12;
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addStoreFilter();
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());
        $collection
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addUrlRewrite();

        $conditions = $this->sliderOptions->getSliderConditions($slider);
        $conditions->collectValidatedAttributes($collection);
        $this->sqlBuilder->attachConditionToCollection($collection, $conditions);
        $this->stock->addIsInStockFilterToCollection($collection);

        $collection->setPageSize($maxCount)->setCurPage(1);
        $collection->distinct(true);

        return $this->sorting->applySorting($options['parameters']['sort_order'] ?? '', $collection);
    }

    public function getImageData(ProductInterface $product, ProductSliderInterface $slider, array $sizes = []): array
    {
        $img = $product->getData('thumbnail');
        $options = $slider->getOptions();
        $options = $options['options'] ?? $options;
        $jpg = isset($options['jpg']) && $options['jpg'] === true;
        if (empty($sizes)) {
            $sizes = $this->getSizes($product);
        }
        $width = $sizes['width'] ?? null;
        $height = $sizes['height'] ?? null;

        try {
            return [
                'formats' => $this->buildImageFormats($options, (string) $img, $width, $height),
                'default' => $this->service->resizeAndConvert((string) $img, $jpg ? 'jpg' : null, $width, $height),
            ];
        } catch (LocalizedException) {
            return [
                'formats' => [],
                'default' => '',
            ];
        }
    }

    public function getSizes(ProductInterface $product): array
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

    public function getFormattedImages(ProductInterface $product, ProductSliderInterface $slider, array $sizes): array
    {
        $imagList = $this->getImageData($product, $slider, $sizes);
        $imageFormats = $imagList['formats'] ?? [];
        if (empty($imageFormats)) {
            if (empty($imagList['default'])) {
                return [];
            }

            //$imageInfo = $this->file->getPathInfo($imageName);

            return [
                'jpg' => [
                    'image' => [
                        'size' => 'default',
                        'link' => $imagList['default'],
                    ],
                ],
            ];
        }
        $result = [];

        foreach ($imageFormats as $ext => $data) {
            if (!isset($result[$ext])) {
                $result[$ext] = [];
            }

            foreach ($data as $imageType => $sizes) {
                if (!isset($result[$ext][$imageType])) {
                    $result[$ext][$imageType] = [];
                }

                foreach ($sizes as $size => $link) {
                    $result[$ext][$imageType][] = [
                        'size' => $size,
                        'link' => $link,
                    ];
                }
            }
        }

        return $result;
    }

    private function buildImageFormats(array $options, ?string $image = null, ?int $width = null, ?int $height = null): array
    {
        if (null === $image) {
            return [];
        }

        $result = [];

        foreach (['avif', 'webp', 'jpg'] as $ext) {
            if (!isset($options[$ext]) || $options[$ext] !== true) {
                continue;
            }

            $result[$ext]['image'] = $this->resizeImage($ext, $image, $width, $height);
        }

        return $result;
    }

    private function resizeImage(?string $ext = null, ?string $image = null, ?int $width = null, ?int $height = null): array
    {
        if ($image === null) {
            return [];
        }
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
