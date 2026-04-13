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

    public function getImageData(ProductInterface $product, ProductSliderInterface $slider, array $imgParams = []): array
    {
        $img = $product->getData('thumbnail');
        $options = $slider->getOptions();
        $options = $options['options'] ?? $options;
        $jpg = isset($options['jpg']) && $options['jpg'] === true;
        $sizes = isset($imgParams['width']) ? $imgParams : null;
        if (!$sizes) {
            $sizes = $this->getSizes($product);
        }
        $width = $sizes['width'] ?? null;
        $height = $sizes['height'] ?? null;
        $options = $imgParams['ext'] ?? $options;

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
        $sizeList = $sizes['sizes'] ?? null;

        if (!$sizeList) {
            return $this->getDefaultFormattedImage($product, $slider);
        }

        return $this->getFormattedImagesForSizes($product, $slider, $sizeList);
    }

    private function getDefaultFormattedImage(ProductInterface $product, ProductSliderInterface $slider): array
    {
        $imageList = $this->getImageData($product, $slider);

        if (empty($imageList['default'])) {
            return [];
        }

        $extension = $this->getFileExtensionFromUrl($imageList['default']);

        return [
            $extension => [
                [
                    'size' => 'default',
                    'link' => $imageList['default'],
                ],
            ],
        ];
    }

    private function getFormattedImagesForSizes(
        ProductInterface $product,
        ProductSliderInterface $slider,
        array $sizes,
    ): array {
        $result = [];

        foreach ($sizes as $size) {
            $imageList = $this->getImageData($product, $slider, ['width' => (int) $size]);

            if (empty($imageList['formats'])) {
                continue;
            }

            $result = $this->mergeFormats($result, $imageList['formats']);
        }

        return $result;
    }

    private function mergeFormats(array $result, array $formats): array
    {
        foreach ($formats as $extension => $images) {
            if (empty($images['image'])) {
                continue;
            }

            foreach ($images['image'] as $size => $link) {
                $result[$extension][] =  [
                    'size' => $size,
                    'link' => $link,
                ];
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

    private function getFileExtensionFromUrl(string $imageUrl): ?string
    {
        $parsedUrl = parse_url($imageUrl, PHP_URL_PATH);

        if (!$parsedUrl) {
            return null;
        }

        $extension = pathinfo($parsedUrl, PATHINFO_EXTENSION);

        return $extension ? strtolower($extension) : null;
    }
}
