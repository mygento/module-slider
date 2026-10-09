<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mygento\Slider\Model\EntityResolverPool;

class EntityType implements OptionSourceInterface
{
    public function __construct(private EntityResolverPool $pool) {}

    public function toOptionArray(): array
    {
        $result = [
            ['value' => 'custom', 'label' => 'custom'],
        ];
        foreach ($this->pool->getAllTypes() as $code) {
            $result[] = ['value' => $code, 'label' => $code];
        }

        return $result;
    }
}
