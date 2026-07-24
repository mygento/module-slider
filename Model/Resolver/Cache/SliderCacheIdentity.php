<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver\Cache;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mygento\Slider\Model\ResourceModel\Slider;

class SliderCacheIdentity implements IdentityInterface
{
    private string $cacheTag = Slider::CACHE_TAG;

    public function __construct(
        private StoreManagerInterface $storeManager,
    ) {}

    public function getIdentities(array $resolvedData): array
    {
        if (empty($resolvedData)) {
            return [];
        }
        $storeId = $this->storeManager->getStore()->getId();

        return [sprintf('%s_%s_%s', $this->cacheTag, $resolvedData['uid'], $storeId)];
    }
}
