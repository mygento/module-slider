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
use Magento\Framework\Filesystem\Io\File;
use Mygento\Slider\Model\Resizer;

class ImageBuilder
{
    private const SUPPORTED_IMG_FORMATS = ['jpg', 'webp', 'avif'];

    public function __construct(
        private Resizer $service,
        private ImageFactory $imageFactory,
        private State $appState,
        private File $file,
    ) {}

    public function resizeImage(string $ext, string $image, ?int $width = null, ?int $height = null): array
    {
        $result = [];
        for ($i = 3; $i >= 1; $i--) {
            try {
                $file = $this->resizeOne($image, $width, $height, $ext, $i);
                if ($file) {
                    $result[] = $file;
                }
            } catch (LocalizedException) {
                continue;
            }
        }

        return $result;
    }

    /**
     * @throws LocalizedException
     */
    public function resizeOne(string $image, ?int $width = null, ?int $height = null, ?string $ext = null, ?int $coeff = 1): ?array
    {
        if (!$ext) {
            $ext = $this->getImageExt($image);
        }
        $file = $this->service->resizeAndConvert(
            $image,
            $ext,
            $width !== null ? $width * $coeff : null,
            $height !== null ? $height * $coeff : null,
        );
        if ($file === null) {
            return null;
        }

        return [
            'size' => ($width * $coeff) . 'w',
            'link' => $file,
        ];
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

    private function getImageExt(string $image): string
    {
        $info = $this->file->getPathInfo($image);

        return (string) $info['extension'];
    }
}
