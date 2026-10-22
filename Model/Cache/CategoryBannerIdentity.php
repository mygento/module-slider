<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Cache;

use Magento\Catalog\Model\Category;
use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

class CategoryBannerIdentity implements IdentityInterface
{
    private const CACHE_TAG = 'cat_bnr';

    /**
     * @param array $resolvedData
     *
     * @return array|string[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getIdentities(array $resolvedData): array
    {
        $cacheTags = [];

        if (!empty($resolvedData['category']['entity_id'])) {
            $categoryId = (int) $resolvedData['category']['entity_id'];
            $cacheTags[] = Category::CACHE_TAG;
            $cacheTags[] = Category::CACHE_TAG . '_' . $categoryId;
            $cacheTags[] = self::CACHE_TAG . '_' . $categoryId;
        }
        $cacheTags[] = self::CACHE_TAG;

        return $cacheTags;
    }
}
