<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Model\DataBuilder\ProductSliderBuilder;
use Mygento\Slider\Model\ProductSliderFactory;
use Psr\Log\LoggerInterface;

class CategoryProductSlider implements ResolverInterface
{
    public function __construct(
        private ProductSliderBuilder $builder,
        private CategoryRepositoryInterface $categoryRepository,
        private Uid $uid,
        private ProductSliderFactory $productSliderFactory,
        private LoggerInterface $logger,
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
        $value = $this->normalizeValues($value);
        $categoryPath = $value['id_path'] ?? null;
        if (!$categoryPath) {
            throw new GraphQlNoSuchEntityException(__('Category path arg is required'));
        }

        $categoryId = $this->extractCategoryId($categoryPath);
        /** @var ProductSliderInterface $slider */
        $slider = $this->productSliderFactory->create();

        try {
            $category = $this->categoryRepository->get($categoryId);
            $category->setUid($this->uid->encode($category->getEntityId()));
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        $options['parameters'] = ['sort_order' =>  $value['sort_by'], 'products_count' => $value['products_count']];
        foreach (['jpg', 'webp', 'avif'] as $extension) {
            $options['options'][$extension] = $value[$extension] ? 'true' : 'false';
        }

        $slider->setOptions($options);

        return [
            'category' => $category->getData(),
            'title' => $value['title'],
            'content_text' => $value['param_content_text'],
            'parameters' => $options['parameters'],
            'items' => $this->getProductModels($slider, (int) $categoryId),
        ];
    }

    private function normalizeValues(array $values): array
    {
        foreach ($values as $name => $widgetParam) {
            //trim prefix
            $name = preg_replace('/^param_/', '', $name);
            $values[$name] = $widgetParam;
        }

        return $values;
    }

    private function extractCategoryId(string $path): int
    {
        $parts = explode('/', $path);

        return (int) end($parts);
    }

    private function getProductModels(ProductSliderInterface $slider, int $categoryId): array
    {
        $productsCollection = $this->builder->getCategoryProductCollection($slider, $categoryId);
        $result = [];
        /** @var ProductInterface $product */
        foreach ($productsCollection as $product) {
            $imageFormats = $this->builder->getImageData($slider, $product);
            $imageFormats['product'] = ['model' => $product];
            $imageFormats['sku'] = $product->getSku();

            $result[] = $imageFormats;
        }

        return $result;
    }
}
