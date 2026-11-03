<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver\Slider;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use Mygento\Slider\Api\Data\BannerInterface;
use Mygento\Slider\Model\Slider;

class Identity implements IdentityInterface
{
    /**
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        if (!isset($resolvedData['model'])) {
            return [];
        }

        /** @var Slider $slider */
        $slider = $resolvedData['model'];
        $banners = $resolvedData['banners'] ?? [];
        $result = $slider->getIdentities();
        foreach ($banners as $b) {
            if (isset($b['id'])) {
                $result[] = sprintf('%s_%s', BannerInterface::CACHE_TAG, $b['id']);
            }
        }

        return $result;
    }
}
