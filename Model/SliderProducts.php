<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model;

use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Rule\Model\Condition\Combine;
use Magento\Rule\Model\Condition\Sql\Builder;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Model\Catalog\Sorting;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SliderProducts
{
    public function __construct(
        private Sorting $sorting,
        private Json $serializer,
        private Config $catalogConfig,
        private Stock $stock,
        private CollectionFactory $productCollectionFactory,
        private Rule $rule,
        private Builder $sqlBuilder,
        private Visibility $catalogProductVisibility,
        private SliderImages $sliderImages,
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

        $conditions = $this->getSliderConditions($slider);
        $conditions->collectValidatedAttributes($collection);
        $this->sqlBuilder->attachConditionToCollection($collection, $conditions);
        $this->stock->addIsInStockFilterToCollection($collection);

        $collection->setPageSize($maxCount)->setCurPage(1);
        $collection->distinct(true);

        return $this->sorting->applySorting($options['parameters']['sort_order'] ?? '', $collection);
    }

    public function getImageData(Product $product, array $options): array
    {
        return $this->sliderImages->getImageData($product, $options);
    }

    public function getSizes(Product $product): array
    {
        return $this->sliderImages->getSizes($product);
    }

    private function getSliderConditions(ProductSliderInterface $productSlider): Combine
    {
        $conditions = $this->serializer->unserialize($productSlider->getConditions() ?? '[]');

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
}
