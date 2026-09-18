<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Block;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\App\Http;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Rule\Model\Condition\Sql\Builder;
use Magento\Widget\Helper\Conditions;
use Mygento\Slider\Api\Data\ProductSliderInterface;
use Mygento\Slider\Model\DataBuilder\ProductSliderBuilder;
use Mygento\Slider\Model\ResourceModel;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductSlider extends ProductsList
{
    protected $_template = 'Mygento_Slider::product_slider.phtml';

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private ProductSliderBuilder $builder,
        private ResourceModel\ProductSlider\CollectionFactory $sliderCollectionFactory,
        Context $context,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        Http\Context $httpContext,
        Builder $sqlBuilder,
        Rule $rule,
        Conditions $conditionsHelper,
        array $data = [],
        ?Json $json = null,
        ?LayoutFactory $layoutFactory = null,
        ?EncoderInterface $urlEncoder = null,
        ?CategoryRepositoryInterface $categoryRepository = null,
    ) {
        parent::__construct(
            $context,
            $productCollectionFactory,
            $catalogProductVisibility,
            $httpContext,
            $sqlBuilder,
            $rule,
            $conditionsHelper,
            $data,
            $json,
            $layoutFactory,
            $urlEncoder,
            $categoryRepository,
        );
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

    public function getSizes(Product $product): array
    {
        return $this->builder->getSizes($product);
    }

    public function getOptions(): array
    {
        return $this->getSlider()?->getOptions() ?? [];
    }

    public function getProductSliderIdentifier(): string
    {
        return 'product_slider' . $this->getData('product_slider');
    }

    public function getCollection(): Collection
    {
        return $this->builder->getCollection($this->getSlider());
    }

    public function getIntegerOption(array $options, string $key): ?int
    {
        return isset($options[$key]) && $options[$key] !== null ? (int) $options[$key] : null;
    }

    public function getImageData(Product $product): array
    {
        return $this->builder->getImageData($this->getSlider(), $product);
    }

    public function getTitle(): string
    {
        return $this->getSlider()?->getTitle() ?? $this->getProductSliderIdentifier();
    }

    protected function _toHtml(): string
    {
        if (!$this->getData('product_slider')) {
            return '';
        }

        $slider = $this->getSlider();
        if (!$slider || !$slider->isActive()) {
            return '';
        }

        return parent::_toHtml();
    }

    protected function _beforeToHtml()
    {
        return $this;
    }

    private function getSlider(): ?ProductSliderInterface
    {
        /** @var ResourceModel\ProductSlider\Collection $collection */
        $collection = $this->sliderCollectionFactory->create();
        $collection->addFieldToFilter(ProductSliderInterface::IDENTITY, $this->getData('product_slider'));
        $entity = $collection->getFirstItem();

        if (!$entity->getId()) {
            return null;
        }

        return $entity;
    }
}
