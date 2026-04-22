<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\DataBuilder;

use Magento\Framework\Serialize\SerializerInterface;

class Options
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {}

    public function getOptions(string|array|null $options): array
    {
        if (empty($options)) {
            return [];
        }

        if (is_string($options)) {
            try {
                $options = $this->serializer->unserialize($options);
            } catch (\Exception $e) {
                $options = [];
            }
        }

        foreach ($options as $key => $value) {
            if ($value === '') {
                $options[$key] = null; //convert empty string values to null
            }
        }

        return $options;
    }
}
