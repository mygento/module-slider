<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model;

use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Rule\Model\Condition\Combine;
use Mygento\Slider\Api\Data\ProductSliderInterface;

class SliderOptions
{
    public function __construct(
        private Resizer $service,
        private SerializerInterface $serializer,
        private Rule $rule,
    ) {}

    public function resizeImage(?string $ext = null, ?string $image = null, ?int $width = null, ?int $height = null): array
    {
        $result = [];
        for ($i = 1;$i <= 3;$i++) {
            try {
                $file = $this->service->resizeAndConvert(
                    $image,
                    $ext,
                    $width !== null ? $width * $i : null,
                    $height !== null ? $height * $i : null,
                );
                if ($file === null) {
                    continue;
                }
                $result[($width * $i) . 'w'] = $file;
            } catch (LocalizedException) {
                continue;
            }
        }

        return $result;
    }

    public function getIntegerOption(array $options, string $key): ?int
    {
        return isset($options[$key]) && $options[$key] !== null ? (int) $options[$key] : null;
    }

    public function buildImageFormats(array $options, array $images = []): array
    {
        if (empty($images)) {
            return [];
        }

        $result = [];

        foreach (['avif', 'webp', 'jpg'] as $ext) {
            if (!isset($options[$ext]) || $options[$ext] !== true) {
                continue;
            }

            foreach ($images as $key => $config) {
                $imagePath = $config['path'] ?? null;
                $width = $config['width'] ?? null;
                $height = $config['height'] ?? null;

                if ($imagePath === null) {
                    continue;
                }

                $result[$ext][$key] = $this->resizeImage($ext, $imagePath, $width, $height);
                if (!isset($result['default'])) {
                    $result['default'] = $this->getOriginalImage($imagePath);
                }
            }
        }

        return $result;
    }

    public function getOriginalImage(string $imagePath): ?string
    {
        try {
            return $this->service->getImageLinkData($imagePath);
        } catch (LocalizedException) {
            return null;
        }
    }

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

    public function getParameters(string|array|null $options): array
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

        return [
            'sort_order' => $options['sort_order'] ?? '',
            'products_count' => $options['products_count'] ?? '',
            'breakpoints' => $options['breakpoints'] ?? [],
        ];
    }

    public function getSliderConditions(ProductSliderInterface $productSlider): Combine
    {
        $conditions = $this->serializer->unserialize($productSlider->getConditions() ?? '[]');

        foreach ($conditions as $key => $condition) {
            if (!empty($condition['attribute'])) {
                if (in_array($condition['attribute'], ['special_from_date', 'special_to_date'])) {
                    $conditions[$key]['value'] = date('Y-m-d H:i:s', strtotime($condition['value']));
                }
            }
        }

        $this->rule->loadPost(['conditions' => $conditions]);

        return $this->rule->getConditions();
    }
}
