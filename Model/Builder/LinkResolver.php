<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Builder;

class LinkResolver
{
    private array $dataBuilders;

    public function __construct(array $dataBuilders = [])
    {
        $this->dataBuilders = $this->prepareDataBuilders($dataBuilders);
    }

    public function addLinks(array $items, int $storeId): array
    {
        $indexesByType = [];
        $targetEntityIds = [];
        foreach ($items as $index => $item) {
            $items[$index]['link'] = null;
            $type = $item['entity_type'] ?? null;
            if (!$type) {
                continue;
            }
            $indexesByType[$type][] = $index;
            $targetEntityIds[$type][$item['entity_identifier']] = $item['entity_identifier'];
        }

        foreach ($indexesByType as $type => $indexes) {
            $typeItems = array_intersect_key($items, array_flip($indexes));
            foreach ($this->dataBuilders[$type] ?? [] as $dataBuilder) {
                if ($dataBuilder instanceof DataBuilderInterface) {
                    $typeItems = $dataBuilder->addData($typeItems, $targetEntityIds[$type], $storeId);
                }
            }
            foreach ($typeItems as $index => $item) {
                $items[$index] = $item;
            }
        }

        return $items;
    }

    private function prepareDataBuilders(array $dataBuilders): array
    {
        $buildersByType = [];
        foreach ($dataBuilders as $builderConfig) {
            if (!isset($builderConfig['entity_type'], $builderConfig['class'], $builderConfig['sortOrder'])) {
                continue;
            }

            $buildersByType[$builderConfig['entity_type']][] = [
                'class' => $builderConfig['class'],
                'sortOrder' => $builderConfig['sortOrder'],
            ];
        }

        foreach ($buildersByType as $type => $builders) {
            usort($builders, function ($a, $b) {
                return $a['sortOrder'] <=> $b['sortOrder'];
            });

            $buildersByType[$type] = array_column($builders, 'class');
        }

        return $buildersByType;
    }
}
