<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mygento\Slider\Api\Data\BannerInterface;
use Mygento\Slider\Model\ResourceModel\Banner;
use Mygento\Slider\Model\Source\EntityType;

class MigrateBannerLinkToEntityType implements DataPatchInterface
{
    public function __construct(private ModuleDataSetupInterface $moduleDataSetup) {}

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->update(
            $this->moduleDataSetup->getTable(Banner::TABLE_NAME),
            [BannerInterface::ENTITY_TYPE => EntityType::CUSTOM],
            [
                BannerInterface::ENTITY_TYPE . ' IS NULL',
                BannerInterface::ENTITY_IDENTIFIER . ' IS NOT NULL',
                BannerInterface::ENTITY_IDENTIFIER . " != ''",
            ],
        );

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
