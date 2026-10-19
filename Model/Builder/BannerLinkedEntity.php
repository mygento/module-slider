<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Builder;

use Mygento\Slider\Model\EntityResolverPool;

class BannerLinkedEntity
{
    public function __construct(private EntityResolverPool $poolResolver) {}

    public function addEntities(array $items, int $storeId, array $requestedFields = []): array
    {
        $idsByType = $this->collectIdsByType($items);
        $resolved = $this->resolveEntities($idsByType, $storeId, $requestedFields);

        foreach ($items as &$item) {
            $this->addEntityIdentitiesField($item, $resolved);
        }

        return $items;
    }

    private function addEntityIdentitiesField(array &$item, array $resolved): void
    {
        $type = $item['entity_type'] ?? null;
        $identifier = $item['entity_identifier'] ?? null;

        if (!$type || !$identifier) {
            return;
        }

        $resolvedEntity = $resolved[$type][$identifier] ?? null;

        if (!$resolvedEntity) {
            return;
        }
        $item['entity'] = $resolvedEntity;
        $item['entity']['entity_type'] = $type;
    }

    private function collectIdsByType(array $items): array
    {
        $idsByType = [];

        foreach ($items as $item) {
            $type = $item['entity_type'] ?? null;
            $identifier = $item['entity_identifier'] ?? null;

            if (!$type || !$identifier) {
                continue;
            }

            $idsByType[$type][] = (string) $identifier;
        }

        return $idsByType;
    }

    private function resolveEntities(array $idsByType, int $storeId, array $requestedFields = []): array
    {
        $resolved = [];

        foreach ($idsByType as $type => $ids) {
            $resolver = $this->poolResolver->get($type);

            if (!$resolver) {
                continue;
            }

            $resolved[$type] = $resolver->resolveData(
                array_unique($ids),
                $storeId,
                $requestedFields,
            );
        }

        return $resolved;
    }
}
