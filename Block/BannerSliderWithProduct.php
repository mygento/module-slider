<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Block;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Mygento\Slider\Model\DataBuilder\BannerBuilder;
use Mygento\Slider\Model\DataBuilder\CatalogEntityResolver;
use Mygento\Slider\Model\ResourceModel\Slider;

class BannerSliderWithProduct extends BannerSlider
{
    public function __construct(
        private CatalogEntityResolver $catalogEntityResolver,
        private ProductRepositoryInterface $productRepository,
        BannerBuilder $builder,
        Slider\CollectionFactory $sliderCollectionFactory,
        Template\Context $context,
        array $data = [],
    ) {
        parent::__construct($builder, $sliderCollectionFactory, $context, $data);
    }

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
        return 'banner_slider_with_product_' . $this->catalogEntityResolver->getEntityIdByPath($this->getData());
    }

    public function getProduct(): ?ProductInterface
    {
        $productId = $this->catalogEntityResolver->getEntityIdByPath($this->getData());
        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return $product;

    }
}
