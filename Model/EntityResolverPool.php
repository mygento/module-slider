<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Model;

use Mygento\Slider\Api\EntityResolverInterface;

class EntityResolverPool
{
    /**
     * @param array<string, EntityResolverInterface> $resolvers
     */
    public function __construct(private array $resolvers = []) {}

    /**
     * @return EntityResolverInterface|null
     */
    public function get(string $type): ?object
    {
        return $this->resolvers[$type] ?? null;
    }

    /**
     * Check whether a resolver exists.
     */
    public function has(string $type): bool
    {
        return isset($this->resolvers[$type]);
    }

    /**
     * Get all registered resolvers types
     *
     * @return array<string>
     */
    public function getAllTypes(): array
    {
        return array_keys($this->resolvers);
    }
}
