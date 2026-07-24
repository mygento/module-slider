<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class EntityType implements OptionSourceInterface
{
    public const CUSTOM = 'custom';
    public const CMS_PAGE = 'cms_page';
    public const CATALOG_PRODUCT = 'catalog_product';
    public const CATALOG_CATEGORY = 'catalog_category';

    public function __construct(private array $types = []) {}

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->types as $code => $type) {
            $options[] = ['value' => $code, 'label' => $type];
        }

        return $options;
    }
}
