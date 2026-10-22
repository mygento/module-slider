<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Cache;

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
        $cacheTags[] = self::CACHE_TAG;

        return $cacheTags;
    }
}
