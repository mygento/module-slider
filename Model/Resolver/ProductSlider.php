<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Model\ProductSliderProducts;
use Mygento\Slider\Model\ResourceModel\ProductSlider\CollectionFactory;
use Mygento\Slider\Model\SliderOptions;

class ProductSlider implements ResolverInterface
{
    private ?array $products = null;

    public function __construct(
        private CollectionFactory $sliderCollectionFactory,
        private ProductSliderProducts $sliderProducts,
        private SliderOptions $sliderOptions,
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
        /** @var ProductSliderInterface $slider */
        $slider = $this->getSlider($identity);
        if (!$slider->getId()) {
            throw new GraphQlNoSuchEntityException(__('Slider not found or disabled'));
        }
        $imgExtensions = $info->getFieldSelection(2)['items']['image_formats'] ?? [];

        return $this->getSliderProducts($slider, $imgExtensions);
    }

    private function getSliderProducts(ProductSliderInterface $slider, array $imgExtensions = []): array
    {
        if (isset($this->products[$slider->getIdentity()])) {
            return $this->products[$slider->getIdentity()];
        }
        $this->products[$slider->getIdentity()] = $this->prepareProductsData($slider, $imgExtensions);

        return $this->products[$slider->getIdentity()];
    }

    private function prepareProductsData(ProductSliderInterface $slider, array $imgExtensions = []): array
    {
        $products = $this->sliderProducts->getProductCollection($slider);
        $productModels = [];
        $options = $slider->getOptions();
        if (empty($options['parameters']['breakpoints'])) {
            $imageFormats = [];
        }
        foreach ($products as $product) {
            $imageFormats = $imageFormats ?? $this->prepareImages($slider, $product, $imgExtensions);
            $productModels[] = ['product' => ['model' => $product], 'image_formats' => $imageFormats, 'sku' => $product->getSku()];
        }

        return [
            'title' => $slider->getTitle(),
            'identity' => $slider->getIdentity(),
            'options' => $this->sliderOptions->getOptions($options['options'] ?? ''),
            'parameters' => $this->sliderOptions->getParameters($options['parameters'] ?? ''),
            'is_active' => $slider->isActive(),
            'items' => $productModels,
        ];
    }

    private function prepareImages(ProductSliderInterface $slider, ProductInterface $product, array $imgExtensions = []): array
    {
        $options = $slider->getOptions();

        $sizes = [];
        foreach ($options['parameters']['breakpoints'] as $breakpoint) {
            if (empty($breakpoint['width'])) {
                continue;
            }
            $sizes[$breakpoint['width']] = $breakpoint['width'] ?? null;
        }
        rsort($sizes); //large resolution first

        return $this->sliderProducts->getFormattedImages(
            $product,
            $slider,
            ['sizes' => $sizes, 'ext' => $imgExtensions],
        );
    }

    private function getSlider(string $identity): ?ProductSliderInterface
    {
        /** @var \Mygento\Slider\Model\ResourceModel\ProductSlider\Collection $collection */
        $collection = $this->sliderCollectionFactory->create();
        $collection->addFieldToFilter(ProductSliderInterface::IDENTITY, $identity);
        $collection->addFieldToFilter(ProductSliderInterface::IS_ACTIVE, 1);

        return $collection->getFirstItem();
    }
}
