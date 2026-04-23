<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Api\ProductSliderRepositoryInterface;
use Mygento\Slider\Model\DataBuilder\ProductSliderDataBuilder;

class ProductSlider implements ResolverInterface
{
    public function __construct(
        private ProductSliderRepositoryInterface $productSliderRepository,
        private ProductSliderDataBuilder $productSliderDataBuilder,
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
        $identity = $args['param_product_slider'] ?? $value['param_product_slider'] ?? null;
        if (!$identity) {
            throw new GraphQlNoSuchEntityException(__('Product Slider Identity arg is required'));
        }

        /** @var ProductSliderInterface $slider */
        try {
            $slider = $this->productSliderRepository->getByIdentity($identity);
        } catch (LocalizedException) {
            throw new GraphQlNoSuchEntityException(__('Product Slider "%1" not found or disabled', $identity));
        }
        if (!$slider->isActive()) {
            throw new GraphQlNoSuchEntityException(__('Product Slider "%1" not found or disabled', $identity));
        }

        return [
            'title' => $slider->getTitle(),
            'identity' => $slider->getIdentity(),
            'options' => $slider->getOptions(false),
            'parameters' => $slider->getParameters(),
            'items' => $this->productSliderDataBuilder->getProductModels($slider),
        ];
    }
}
