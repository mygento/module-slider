<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Api;

interface EntityResolverInterface
{
    /**
     * @param string[] $ids
     * @return array<string, string>
     */
    public function resolveName(array $ids): array;

    /**
     * @param string[] $ids
     * @return array<string, array{url: string|null, entity_identifier: string}>
     */
    public function resolveData(array $ids, int $storeId): array;
}
