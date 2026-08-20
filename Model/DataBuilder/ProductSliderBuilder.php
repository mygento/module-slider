<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\DataBuilder;

use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Rule\Model\Condition\Combine;
use Magento\Rule\Model\Condition\Sql\Builder;
use Mygento\ImageCommon\Model\Resizer;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Model\Catalog\Sorting;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductSliderBuilder
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private Resizer $service,
        private Sorting $sorting,
        private Visibility $catalogProductVisibility,
        private Config $catalogConfig,
        private Json $serializer,
        private Builder $sqlBuilder,
        private Stock $stock,
        private Rule $rule,
        private State $appState,
        private CollectionFactory $productCollectionFactory,
        private ImageFactory $imageFactory,
    ) {}

    public function getSizes(Product $product): array
    {
        if ($this->appState->getAreaCode() !== Area::AREA_FRONTEND) {
            /** @var Image $image */
            $image = $this->appState->emulateAreaCode(
                Area::AREA_FRONTEND,
                [$this->imageFactory, 'create'],
                [$product, 'category_page_grid'],
            );
        } else {
            /** @var Image $image */
            $image = $this->imageFactory->create($product, 'category_page_grid');
        }
        $width = $image->getWidth();
        $height = $image->getHeight();

        return [
            'width' => $width,
            'height' => $height,
        ];
    }

    public function getCollection(ProductSliderInterface $slider): Collection
    {
        $options = $slider->getOptions();
        $maxCount = $options['products_count'] ?? 12;

        /** @var Collection $collection */
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

    public function getImageData(ProductSliderInterface $slider, Product $product): array
    {
        $img = $product->getData('thumbnail');
        $options = $slider->getOptions();
        $jpg = isset($options['jpg']) && $options['jpg'] === true;
        $sizes = $this->getSizes($product);
        $width = $sizes['width'] ?? null;
        $height = $sizes['height'] ?? null;

        if ($img === null || $width === null) {
            return [
                'image_formats' => [],
                'default' => '#',
                'image' => '#',
            ];
        }

        try {
            $defaultImg = $this->service->execute(
                imagePath: $img,
                width: $width,
                height: $height,
                ext: $jpg ? 'jpg' : null,
            );

            return [
                'image_formats' => $this->buildImageFormats($options['options'], $img, $width, $height),
                'default' => $defaultImg['url'],
                'image' => $defaultImg['url'],
            ];
        } catch (LocalizedException) {
            return [
                'image_formats' => [],
                'default' => '#',
                'image' => '#',
            ];
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
        $result = [];

        foreach (['avif', 'webp', 'jpg'] as $ext) {
            if (!isset($options[$ext]) || $options[$ext] !== true) {
                continue;
            }
            $result[$ext] = $this->service->execute(
                imagePath: $image,
                width: $width,
                height: $height,
                ext: $ext,
            );
        }

        return $result;
    }
}
