<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver\Slider;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use Mygento\Slider\Api\Data\SliderInterface;

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
        /** @var SliderInterface $slider */
        $slider = $resolvedData['model'];

        return $slider->getIdentities();
    }
}
