<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

class SliderProductsCollectionProcessor implements CollectionProcessorInterface
{
    /**
     * @param CollectionProcessorInterface[] $collectionProcessors
     */
    public function __construct(private array $collectionProcessors = []) {}

    /**
     * Process collection to add additional joins, attributes, and clauses to a product collection.
     *
     * @param Collection $collection
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $attributeNames
     * @param ContextInterface|null $context
     * @return Collection
     */
    public function process(
        Collection $collection,
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames,
        ?ContextInterface $context = null,
    ): Collection {
        foreach ($this->collectionProcessors as $collectionProcessor) {
            $collection = $collectionProcessor->process($collection, $searchCriteria, $attributeNames, $context);
        }

        return $collection;
    }
}
