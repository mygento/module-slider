<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\DataBuilder;

class CatalogEntityResolver
{
    public function getEntityIdByPath(array $data): int
    {
        $value = $this->normalizeValues($data);
        $categoryPath = $value['id_path'] ?? null;
        if (empty($categoryPath)) {
            throw new \InvalidArgumentException('Path is empty');
        }

        return $this->extractCategoryId($categoryPath);
    }

    public function normalizeValues(array $values): array
    {
        foreach ($values as $name => $widgetParam) {
            //trim prefix from graphql widget params
            $name = preg_replace('/^param_/', '', $name);
            $values[$name] = $widgetParam;
        }

        return $values;
    }

    private function extractCategoryId(string $path): int
    {
        $parts = explode('/', $path);

        return (int) end($parts);
    }
}
