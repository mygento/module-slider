<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Mygento\Slider\Model\Source\EntityType;

class EntityLabelResolver
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function resolve(string $entityType, ?string $entityId): ?string
    {
        try {
            return match ($entityType) {
                EntityType::CMS_PAGE => sprintf(
                    '[Page ID: %d] %s',
                    $entityId,
                    $this->pageRepository->getById($entityId)->getTitle(),
                ),
                EntityType::CATALOG_PRODUCT => sprintf(
                    '[Product ID: %d] %s',
                    $entityId,
                    $this->productRepository->getById($entityId)->getName(),
                ),
                default => null,
            };
        } catch (LocalizedException) {
            return null;
        }
    }
}
