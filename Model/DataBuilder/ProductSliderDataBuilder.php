<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\DataBuilder;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Rule\Model\Condition\Combine;
use Magento\Rule\Model\Condition\Sql\Builder;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Model\Catalog\Sorting;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductSliderDataBuilder
{
    private ?array $sliderProducts = null;

    public function __construct(
        private Sorting $sorting,
        private Json $serializer,
        private Config $catalogConfig,
        private Stock $stock,
        private CollectionFactory $productCollectionFactory,
        private Visibility $catalogProductVisibility,
        private Builder $sqlBuilder,
        private Rule $rule,
        private ImageBuilder $imageBuilder,
    ) {}

    public function getCollection(ProductSliderInterface $slider): Collection
    {
        $options = $slider->getOptions();
        $maxCount = $options['products_count'] ?? 12;
        $collection = $this->productCollectionFactory->create();
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());
        $collection
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addUrlRewrite()
            ->addStoreFilter();

        $conditions = $this->getSliderConditions($slider);
        $conditions->collectValidatedAttributes($collection);
        $this->sqlBuilder->attachConditionToCollection($collection, $conditions);
        $this->stock->addIsInStockFilterToCollection($collection);

        $collection->setPageSize($maxCount)->setCurPage(1);
        $collection->distinct(true);

        return $this->sorting->applySorting($options['parameters']['sort_order'] ?? '', $collection);
    }

    public function getProductModels(ProductSliderInterface $slider): array
    {
        if (isset($this->sliderProducts[$slider->getId()])) {
            return $this->sliderProducts[$slider->getId()];
        }
        $productsCollection = $this->getCollection($slider);
        /** @var ProductInterface $product */
        foreach ($productsCollection as $product) {
            $imageFormats = $this->getImageData($slider, $product);
            $this->sliderProducts[$slider->getId()][] = [
                'product' => ['model' => $product], 'image_formats' => $imageFormats, 'sku' => $product->getSku(),
            ];
        }

        return $this->sliderProducts[$slider->getId()];
    }

    public function getImageData(ProductSliderInterface $slider, Product $product): array
    {
        $img = $product->getData('thumbnail');
        $options = $slider->getOptions();
        $options = $options['options'];
        $jpg = isset($options['jpg']) && $options['jpg'] === true;
        $sizes = $this->imageBuilder->getSizes($product);
        $width = $sizes['width'] ?? null;
        $height = $sizes['height'] ?? null;

        try {
            $result = $this->buildImageFormats($options, $img ?? null, $width, $height);
            $result['default'] = ['size' => 'default', 'link' => $this->imageBuilder->resizeAndConvert($img ?? null, $jpg ? 'jpg' : null, $width, $height)];

            return $result;
        } catch (LocalizedException) {
            return [];
        }
    }

    private function getSliderConditions(ProductSliderInterface $slider): Combine
    {
        $conditions = $this->serializer->unserialize($slider->getConditions() ?? '[]');

        foreach ($conditions as $key => $condition) {
            if (!empty($condition['attribute'])) {
                if (in_array($condition['attribute'], ['special_from_date', 'special_to_date'])) {
                    $conditions[$key]['value'] = date('Y-m-d H:i:s', strtotime($condition['value']));
                }
            }
        }

        $this->rule->loadPost(['conditions' => $conditions]);

        return $this->rule->getConditions();
    }

    private function buildImageFormats(array $options, ?string $image = null, ?int $width = null, ?int $height = null): array
    {
        if (null === $image) {
            return [];
        }

        $formats = [];
        $supportedFormats = $this->imageBuilder->getSupportedFormats($options);

        foreach ($supportedFormats as $ext) {
            $formats[$ext] = $this->imageBuilder->resizeImage($ext, $image, $width, $height);
        }

        return $formats;
    }
}
