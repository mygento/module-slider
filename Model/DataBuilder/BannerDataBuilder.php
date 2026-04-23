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
        private ImageBuilder $imageBuilder,
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
                $item['image_formats']['default']['image'][] = ['link' =>  $defaultImage, 'size' => 'default'];
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
        $supportedFormats = $this->imageBuilder->getSupportedFormats($options);

        foreach ($supportedFormats as $format) {
            $imageData = $this->buildImageDataForFormat($format, $options, $image, $smallImage);

            if (!empty($imageData)) {
                $result[$format] = $imageData;
            }
        }

        return $result;
    }

    private function buildImageDataForFormat(string $format, array $options, ?string $image, ?string $smallImage = null): array
    {
        if (null === $image) {
            return [];
        }
        $imageData = [];

        $mainImageData = $this->processImage($format, $options, $image);
        if (!empty($mainImageData)) {
            $imageData['image'] = $mainImageData;
        }

        if (null == $smallImage) {
            return $imageData;
        }
        // Process small_image if provided
        $smallImageData = $this->processImage($format, $options, $image, '_small');
        if (!empty($smallImageData)) {
            $imageData['small_image'] = $smallImageData;
        }

        return $imageData;
    }

    private function processImage(string $format, array $options, string $image, string $type = ''): ?array
    {
        return $this->imageBuilder->resizeImage($format, $image, (int) $options['width' . $type], (int) $options['height' . $type]);
    }
}
