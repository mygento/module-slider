<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Mygento\Slider\Model\EntityResolverPool;

class Name extends Column
{
    public function __construct(
        private EntityResolverPool $pool,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = [],
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $idsByType = $this->collectIdsByType($dataSource['data']['items']);
        $resolved = $this->resolveNames($idsByType);

        foreach ($dataSource['data']['items'] as &$item) {
            $this->setEntityName($item, $resolved);
        }

        return $dataSource;
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

    private function resolveNames(array $idsByType): array
    {
        $resolved = [];

        foreach ($idsByType as $type => $ids) {
            $resolver = $this->pool->get($type);

            if (!$resolver) {
                continue;
            }

            $resolved[$type] = $resolver->resolveName(array_unique($ids));
        }

        return $resolved;
    }

    private function setEntityName(array &$item, array $resolved): void
    {
        $type = $item['entity_type'] ?? null;
        $identifier = $item['entity_identifier'] ?? null;

        if (!$type || !$identifier) {
            $item[$this->getName()] = $identifier;

            return;
        }

        $item[$this->getName()] = $resolved[$type][(string) $identifier] ?? null;
    }
}
