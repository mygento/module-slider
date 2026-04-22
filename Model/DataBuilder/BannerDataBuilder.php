<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\DataBuilder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Mygento\Slider\Api\Data\BannerInterface;
use Mygento\Slider\Api\Data\SliderInterface;
use Mygento\Slider\Model\Resizer;
use Mygento\Slider\Model\ResourceModel\Banner;
use Psr\Log\LoggerInterface;

class BannerDataBuilder
{
    public function __construct(
        private Resizer $service,
        private StoreManagerInterface $storeManager,
        private Banner\CollectionFactory $factory,
        private DateTime $date,
        private LoggerInterface $logger,
    ) {}

    public function getImages(SliderInterface $slider): array
    {
        $options = $slider->getOptionsList();
        $currentDate = $this->date->gmtDate();

        /** @var Banner\Collection $collection */
        $collection = $this->factory->create();
        $collection->addFieldToFilter(BannerInterface::IS_ACTIVE, 1);
        $collection->fetchItemsWithPositionBySlider((int) $slider->getId());
        $collection->addFilter(
            BannerInterface::STORE_ID,
            ['in' => $this->storeManager->getStore()->getId()],
        );
        $collection->addFieldToFilter(BannerInterface::FROM_DATE, [
            ['null' => true],
            ['lteq' => $currentDate],
        ]);
        $collection->addFieldToFilter(BannerInterface::TO_DATE, [
            ['null' => true],
            ['gteq' => $currentDate],
        ]);

        $result = [];
        foreach ($collection as $entity) {
            try {
                $item = $entity->getData();
                $item['image_formats'] = $this->buildImageFormats($options, $item['image'] ?? null, $item['small_image'] ?? null);
                $defaultImage = $this->service->getImagePath($item['image']);
                $item['image_formats']['default']['image'] = ['link' =>  $defaultImage, 'size' => 'default'];
                $result[] = $item;
            } catch (LocalizedException $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
        }

        return $result;
    }

    private function buildImageFormats(array $options, ?string $image = null, ?string $smallImage = null): array
    {
        if (null === $image) {
            return [];
        }
        $result = [];

        foreach (['avif', 'webp', 'jpg'] as $ext) {
            if (!isset($options[$ext]) || $options[$ext] !== true) {
                continue;
            }

            $link = $this->resizeImage($ext, $image, $options['width'], $options['height']);
            $result[$ext]['image'] = $link;
            if ($smallImage === null) {
                continue;
            }
            $result[$ext]['small_image'] = $this->resizeImage($ext, $smallImage, $options['width_small'], $options['height_small']);
        }

        return $result;
    }

    private function resizeImage(?string $ext = null, ?string $image = null, ?int $width = null, ?int $height = null): array
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
                $result = [
                    'size' => ($width * $i) . 'w',
                    'link' => $file,
                ];
            } catch (LocalizedException) {
                continue;
            }
        }

        return $result;
    }
}
