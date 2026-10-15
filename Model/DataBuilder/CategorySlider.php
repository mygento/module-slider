<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\DataBuilder;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogGraphQl\Model\Category\Hydrator;
use Magento\Framework\Exception\NoSuchEntityException;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Model\ProductSliderFactory;

class CategorySlider
{
    public function __construct(
        private ProductSliderBuilder $builder,
        private CategoryRepositoryInterface $categoryRepository,
        private ProductSliderFactory $productSliderFactory,
        private Hydrator $categoryHydrator,
    ) {}

    /**
     * @param array $value
     *
     * @throws NoSuchEntityException
     * @return array
     */
    public function resolverSlider(array $value): array
    {
        $categoryId = $this->getCategoryId($value);
        $category = $this->categoryRepository->get($categoryId);
        $slider = $this->createSlider($value);
        $options = $slider->getOptions();

        return [
            'category' => $this->categoryHydrator->hydrateCategory($category, true),
            'title' => $value['title'],
            'content_text' => $value['param_content_text'],
            'parameters' => $options['parameters'] ?? [],
            'items' => $this->getProductModels($slider, $category),
        ];
    }

    public function createSlider(array $data): ProductSliderInterface
    {
        $value = $this->normalizeValues($data);
        /** @var ProductSliderInterface $slider */
        $slider = $this->productSliderFactory->create();
        $options['parameters'] = ['sort_order' =>  $value['sort_by'], 'products_count' => $value['products_count']];
        foreach (['jpg', 'webp', 'avif'] as $extension) {
            $ext = $value[$extension] ?? 0;
            $options['options'][$extension] = $ext ? 'true' : 'false';
        }
        $slider->setOptions($options);
        $slider->setActive(true);

        return $slider;
    }

    public function getCategoryId(array $data): int
    {
        $value = $this->normalizeValues($data);
        $categoryPath = $value['id_path'] ?? null;
        if (empty($categoryPath)) {
            throw new \InvalidArgumentException('Category path is empty');
        }

        return $this->extractCategoryId($categoryPath);
    }

    private function extractCategoryId(string $path): int
    {
        $parts = explode('/', $path);

        return (int) end($parts);
    }

    private function getProductModels(ProductSliderInterface $slider, CategoryInterface $category): array
    {
        $productsCollection = $this->builder->getCategoryProductCollection($slider, $category);
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

    private function normalizeValues(array $values): array
    {
        foreach ($values as $name => $widgetParam) {
            //trim prefix from graphql widget params
            $name = preg_replace('/^param_/', '', $name);
            $values[$name] = $widgetParam;
        }

        return $values;
    }
}
