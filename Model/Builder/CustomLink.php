<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Builder;

class CustomLink implements DataBuilderInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addData(array $items, array $entityIds, int $storeId): array
    {
        foreach ($items as &$item) {
            $item['link'] = $item['entity_identifier'] ?? null;
        }

        return $items;
    }
}
