<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\ReviewRendererInterface;
use Magento\Catalog\Helper\Product\Compare;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Helper\Data;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Api\ProductSliderRepositoryInterface;
use Mygento\Slider\Model\SliderOptions;
use Mygento\Slider\Model\SliderProducts;

class ProductSlider implements ResolverInterface
{
    private ?array $products = null;

    public function __construct(
        private ProductSliderRepositoryInterface $productSliderRepository,
        private SliderProducts $sliderProducts,
        private ReviewRendererInterface $productReviewRenderer,
        private Data $wishlistHelper,
        private Compare $compareHelper,
        private SliderOptions $sliderOptions,
    ) {}

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null,
    ) {
        $identity = $args['identity'] ?? null;

        if (!$identity) {
            throw new GraphQlNoSuchEntityException(__('Slider Identity arg is required'));
        }

        try {
            /** @var ProductSliderInterface $slider */
            $slider = $this->productSliderRepository->getByIdentity($identity);

            return $this->getSliderProducts($slider);
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }
    }

    private function getSliderProducts(ProductSliderInterface $slider): array
    {
        if (isset($this->products[$slider->getIdentity()])) {
            return $this->products[$slider->getIdentity()];
        }
        $this->products[$slider->getIdentity()] = $this->prepareProductsData($slider);

        return $this->products[$slider->getIdentity()];
    }

    private function prepareProductsData(ProductSliderInterface $slider): array
    {
        $products = $this->sliderProducts->getProductCollection($slider);
        $productsData = [];
        $options = $slider->getOptions();

        /** @var ProductInterface $product */
        foreach ($products as $product) {
            $productsImages = $this->sliderProducts->getImageData($product, $options['options']);
            $productSizes = $this->sliderProducts->getSizes($product);
            $productsData[] = [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'url' => $product->getProductUrl(),
                'details' => $this->productReviewRenderer->getReviewsSummaryHtml($product, ReviewRendererInterface::SHORT_VIEW),
                'is_saleable' => $product->isSaleable(),
                'is_available' => $product->isAvailable(),
                'add_to_wishlist_params' => $this->wishlistHelper->isAllow() ? $this->wishlistHelper->getAddToCartParams($product) : '',
                'add_to_compare_params' => $this->compareHelper->getPostDataParams($product),
                'image_formats' => $productsImages['formats'] ?? null,
                'image_default' => $productsImages['default'] ?? null,
                'width' => $productSizes['width'],
                'height' => $productSizes['height'],
            ];
        }

        return [
            'title' => $slider->getTitle(),
            'identity' => $slider->getIdentity(),
            'options' => $this->sliderOptions->getOptions($options['options'] ?? ''),
            'parameters' => $this->sliderOptions->getParameters($options['parameters'] ?? ''),
            'is_active' => $slider->isActive(),
            'products' => $productsData,
        ];
    }
}
