<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Cache;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

class CategoryBannerIdentity implements IdentityInterface
{
    public const CACHE_TAG = 'category_banner';

    public function getIdentities(array $resolvedData): array
    {
        $cacheTags = [];

        if (!empty($resolvedData['category']['entity_id'])) {
            $categoryId = (int) $resolvedData['category']['entity_id'];
            $cacheTags[] = Category::CACHE_TAG . '_' . $categoryId;
            $cacheTags[] = self::CACHE_TAG . '_' . $categoryId;
            $cacheTags[] = Category::CACHE_TAG;
            $cacheTags[] = Product::CACHE_PRODUCT_CATEGORY_TAG . '_' . $categoryId;
        }

        $cacheTags[] = Product::CACHE_TAG;
        $items = $resolvedData['items'] ?? [];
        foreach ($items as $item) {
            $productId = $item['product']['model']->getId() ?? null;
            if ($productId) {
                $cacheTags[] = Product::CACHE_TAG . '_' . $productId;
            }
        }

        $cacheTags[] = self::CACHE_TAG;

        return array_unique($cacheTags);
    }
}
