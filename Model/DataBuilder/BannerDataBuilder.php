<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\DataBuilder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Mygento\Slider\Api\Data\BannerInterface;
use Mygento\Slider\Api\Data\SliderInterface;
use Mygento\Slider\Model\ResourceModel\Banner;
use Psr\Log\LoggerInterface;

class BannerDataBuilder
{
    private array $sliderBanners = [];

    public function __construct(
        private StoreManagerInterface $storeManager,
        private Banner\CollectionFactory $factory,
        private DateTime $date,
        private LoggerInterface $logger,
        private ImageBuilder $imageBuilder,
        private Uid $idEncoder,
    ) {}

    public function getImages(SliderInterface $slider): array
    {
        $storeId = $this->storeManager->getStore()->getId();
        if (isset($this->sliderBanners[$slider->getId()][$storeId])) {
            return $this->sliderBanners[$slider->getId()][$storeId];
        }
        $options = $this->prepareData($slider->getOptionsList());
        $currentDate = $this->date->gmtDate();

        /** @var Banner\Collection $collection */
        $collection = $this->factory->create();
        $collection->addFieldToFilter(BannerInterface::IS_ACTIVE, 1);
        $collection->fetchItemsWithPositionBySlider((int) $slider->getId());
        $collection->addFilter(
            BannerInterface::STORE_ID,
            ['in' => $storeId],
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
        /** @var BannerInterface $entity */
        foreach ($collection as $entity) {
            try {
                $item = $this->prepareData($entity->getData());
                $item['image_formats'] = $this->buildImageFormats($options, $item);
                $item['uid'] = $this->idEncoder->encode((string) $entity->getId());
                $result[] = $item;
            } catch (LocalizedException $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
        }
        $this->sliderBanners[$slider->getId()][$storeId] = $result;

        return $result;
    }

    public function prepareData(array $data): array
    {
        //convert empty values to null
        return array_map(function ($value) {
            return match (true) {
                $value === '' => null,
                is_numeric($value) => (int) $value,
                default => $value,
            };
        }, $data);
    }

    private function buildImageFormats(array $options, array $item): ?array
    {
        $image = $item['image'] ?? null;
        $smallImage = $item['small_image'] ?? null;

        if (null === $image && null === $smallImage) {
            return null;
        }
        $result = $this->buildDefaultImages($options, $image, $smallImage);

        $supportedFormats = $this->imageBuilder->getSupportedFormats($options);
        foreach ($supportedFormats as $format) {
            $mainImageData = $this->processImage($format, $options, $image);
            if (!empty($mainImageData)) {
                $result[$format]['image'] = $mainImageData;
            }

            if (null == $smallImage) {
                // Process main image as small_image
                $smallImage = $image;
            }
            $smallImageData = $this->processImage($format, $options, $smallImage, '_small');
            if (!empty($smallImageData)) {
                $result[$format]['small_image'] = $smallImageData;
            }
        }

        return $result;
    }

    private function processImage(string $format, array $options, string $image, string $configPrefix = ''): ?array
    {
        return $this->imageBuilder->resizeImage($format, $image, $options['width' . $configPrefix], $options['height' . $configPrefix]);
    }

    private function buildDefaultImages(array $options, ?string $image, ?string $smallImage): array
    {
        $result = [];

        $mainImageData = $this->getResizedImage($options, $image);
        if (!empty($mainImageData['link'])) {
            $result['default']['image'] = $mainImageData['link'];
        }
        if (empty($smallImage)) {
            return $result;
        }

        $smallImageData = $this->getResizedImage($options, $smallImage, '_small');
        if (!empty($smallImageData['link'])) {
            $result['default']['small_image'] = $smallImageData['link'];
        }

        return $result;
    }

    private function getResizedImage(array $options, ?string $image = null, ?string $configPrefix = ''): ?array
    {
        if (!$image) {
            return null;
        }

        try {
            $imageData = $this->imageBuilder->resizeOne($image, $options['width' . $configPrefix], $options['height' . $configPrefix]);
        } catch (LocalizedException) {
            return null;
        }

        return $imageData;
    }
}
