<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\DataBuilder;

use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Mygento\Slider\Model\Resizer;

class ImageBuilder
{
    private const SUPPORTED_IMG_FORMATS = ['avif', 'webp', 'jpg'];

    public function __construct(
        private Resizer $service,
        private ImageFactory $imageFactory,
        private State $appState,
    ) {}

    public function resizeImage(string $ext = null, string $image = null, ?int $width = null, ?int $height = null): array
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
                $result[] = [
                    'size' => ($width * $i) . 'w',
                    'link' => $file,
                ];
            } catch (LocalizedException) {
                continue;
            }
        }

        return $result;
    }

    /**
     * Get list of supported image formats based on options
     *
     * @param array $options
     * @return array
     */
    public function getSupportedFormats(array $options): array
    {
        return array_filter(self::SUPPORTED_IMG_FORMATS, function ($format) use ($options) {
            return isset($options[$format]) && $options[$format] === true;
        });
    }

    /**
     * @throws LocalizedException
     */
    public function resizeAndConvert(string $imagePath, ?string $ext, ?int $width = null, ?int $height = null): ?string
    {
        return $this->service->resizeAndConvert($imagePath, $ext, $width, $height);
    }

    public function getSizes(Product $product): array
    {
        /** @var Image $image */
        if ($this->appState->getAreaCode() !== Area::AREA_FRONTEND) {
            $image = $this->appState->emulateAreaCode(
                Area::AREA_FRONTEND,
                [$this->imageFactory, 'create'],
                [$product, 'category_page_grid'],
            );
        } else {
            $image = $this->imageFactory->create($product, 'category_page_grid');
        }
        $width = $image->getWidth();
        $height = $image->getHeight();

        return [
            'width' => $width,
            'height' => $height,
        ];
    }
}
