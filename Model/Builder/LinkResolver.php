<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Builder;

use Mygento\Slider\Model\EntityResolverPool;

class LinkResolver
{
    public function __construct(private EntityResolverPool $poolResolver) {}

    /**
     * @param array<int, array{
     *     entity_type?: string|null,
     *     entity_identifier?: int|string|null,
     *     link?: int|string|null,
     * }> $items
     *
     * @return array<int, array{
     *     entity_type?: string|null,
     *     entity_identifier?: int|string|null,
     *     link?: int|string|null,
     * }>
     */
    public function addLinks(array $items, int $storeId): array
    {
        $idsByType = $this->collectIdsByType($items);
        $resolved = $this->resolveEntities($idsByType, $storeId);

        foreach ($items as &$item) {
            $this->addLink($item, $resolved);
        }

        return $items;
    }

    /**
     * @param array<int, array{
     *     entity_type?: string|null,
     *     entity_identifier?: int|string|null,
     * }> $items
     *
     * @return array<string, list<string>>
     */
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

    /**
     * @param array<string, list<string>> $idsByType
     *
     * @return array<string, array<string, array{
     *     entity_identifier: int|string,
     *     url?: string|null,
     * }>>
     */
    private function resolveEntities(array $idsByType, int $storeId): array
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
            );
        }

        return $resolved;
    }

    /**
     * @param array{
     *     entity_type?: string|null,
     *     entity_identifier?: int|string|null,
     *     link?: int|string|null,
     * } $item
     *
     * @param array<string, array<string, array{
     *     entity_identifier: int|string,
     *     url?: string|null,
     * }>> $resolved
     */
    private function addLink(array &$item, array $resolved): void
    {
        $type = $item['entity_type'] ?? null;
        $identifier = $item['entity_identifier'] ?? null;

        $item['link'] = $identifier;

        if (!$type || !$identifier) {
            return;
        }

        $resolvedEntity = $resolved[$type][$identifier] ?? null;

        if (!$resolvedEntity) {
            return;
        }

        $item['entity_identifier'] = $resolvedEntity['entity_identifier'];
        $item['link'] = isset($resolvedEntity['url'])
            ? '/' . $resolvedEntity['url']
            : null;
    }
}
