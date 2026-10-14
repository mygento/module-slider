<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mygento\Slider\Api\SliderRepositoryInterface;
use Mygento\Slider\Model\DataBuilder\BannerBuilder;
use Mygento\Slider\Model\DataBuilder\CatalogEntityResolver;

class BannerSliderWithProduct implements ResolverInterface
{
    public function __construct(
        private SliderRepositoryInterface $repo,
        private BannerBuilder $builder,
        private ProductRepositoryInterface $productRepository,
        private CatalogEntityResolver $entityResolver,
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
        $identity = $args['identity'] ?? $value['identity'] ?? $value['param_identity'] ?? null;
        if (!$identity) {
            throw new GraphQlNoSuchEntityException(__('Product Slider Identity arg is required'));
        }

        try {
            $slider = $this->repo->getByIdentity($identity);
        } catch (LocalizedException) {
            throw new GraphQlNoSuchEntityException(__('Product Slider "%1" not found or disabled', $identity));
        }
        if (!$slider->isActive()) {
            throw new GraphQlNoSuchEntityException(__('Product Slider "%1" not found or disabled', $identity));
        }

        $banners = $this->builder->getImages($slider);
        $productId = $this->entityResolver->getEntityIdByPath($value);
        $product = null;

        try {
            /** @var ProductInterface $product */
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            //nothing to log
        }
        if (!$product || !$product->isAvailable()) {
            throw new GraphQlNoSuchEntityException(__('Product "%1" not found or disabled', $productId));
        }
        $productData = $product->getData();
        $productData['model'] = $product;

        return [
            'title' => $slider->getTitle(),
            'identity' => $slider->getIdentity(),
            'options' => $this->prepareOptions($slider->getOptionsList()),
            'banners' => $banners,
            'product' => $productData,
        ];
    }

    private function prepareOptions(array $options): array
    {
        //convert empty values to null
        return array_map(function ($value) {
            return match (true) {
                $value === '' => null,
                is_numeric($value) => (int) $value,
                default => $value,
            };
        }, $options);
    }
}
