<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver\ProductSlider;

use Magento\Catalog\Model\Product;
use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

class Identity implements IdentityInterface
{
    /**
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $result = [];
        foreach ($resolvedData['items'] ?? [] as $i) {
            if (!isset($i['product']['model'])) {
                continue;
            }
            /** @var Product $p */
            $p = $i['product']['model'];
            $result[] = sprintf('%s_%s', Product::CACHE_TAG, $p->getEntityId());
        }
        if (!empty($result)) {
            array_unshift($result, Product::CACHE_TAG);
        }

        return $result;
    }
}
