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
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Api\ProductSliderRepositoryInterface;
use Mygento\Slider\Model\DataBuilder\ProductSliderDataBuilder;

class ProductSlider implements ResolverInterface
{
    public function __construct(
        private ProductSliderRepositoryInterface $productSliderRepository,
        private ProductSliderDataBuilder $productSliderDataBuilder,
        private Uid $idEncoder,
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
        $identity = $args['identity'] ?? $value['identity'] ?? null;
        if (!$identity) {
            throw new GraphQlNoSuchEntityException(__('Product Slider Identity arg is required'));
        }

        try {
            /** @var ProductSliderInterface $slider */
            $slider = $this->productSliderRepository->getByIdentity($identity);
        } catch (LocalizedException) {
            throw new GraphQlNoSuchEntityException(__('Product Slider "%1" not found or disabled', $identity));
        }
        if (!$slider->isActive()) {
            throw new GraphQlNoSuchEntityException(__('Product Slider "%1" not found or disabled', $identity));
        }

        $options = $slider->getOptions();
        $options = $options['options'];

        return [
            'uid' => $this->idEncoder->encode((string) $slider->getId()),
            'title' => $slider->getTitle(),
            'identity' => $slider->getIdentity(),
            'options' => $options,
            'parameters' => $slider->getParameters(),
            'items' => $this->productSliderDataBuilder->getProductModels($slider),
        ];
    }
}
