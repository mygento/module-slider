<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\EntityResolver;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

class BannerEntityResolver implements TypeResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        $code = $data['entity_type'] ?? '';

        return 'BannerEntity' . str_replace('_', '', ucwords($code, '_'));
    }
}
