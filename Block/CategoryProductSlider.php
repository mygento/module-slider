<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Block;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Mygento\Slider\Api\Data\ProductSliderInterface;

class CategoryProductSlider extends ProductSlider
{
    protected $_template = 'Mygento_Slider::product_slider.phtml';
    private ?ProductSliderInterface $slider = null;

    /**
     * @inheritdoc
     */
    public function getCacheKeyInfo(): array
    {
        $cacheKeyInfo = parent::getCacheKeyInfo();
        $cacheKeyInfo[] = $this->getProductSliderIdentifier();

        return $cacheKeyInfo;
    }

    public function getProductSliderIdentifier(): string
    {
        return 'product_category_slider' . $this->getCategoryId();
    }

    public function getCategoryId(): int
    {
        return $this->builder->getCategoryId($this->getData());
    }

    public function getCategory(): CategoryInterface
    {
        return $this->categoryRepository->get($this->getCategoryId());
    }

    public function getCollection(): ?Collection
    {
        try {
            return $this->builder->getCategoryProductCollection(
                $this->getSlider(),
                $this->getCategory(),
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getSlider(): ProductSliderInterface
    {
        if ($this->slider === null) {
            $this->slider = $this->builder->getSlider($this->getData());
            $this->setData('product_slider', $this->slider);
        }

        return $this->slider;
    }
}
