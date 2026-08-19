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
use Mygento\ImageCommon\Model\Resizer;
use Mygento\Slider\Api\Data\BannerInterface;
use Mygento\Slider\Api\Data\SliderInterface;
use Mygento\Slider\Model\Builder\LinkResolver;
use Mygento\Slider\Model\ResourceModel\Banner;

class BannerBuilder
{
    public function __construct(
        private Resizer $service,
        private LinkResolver $linkResolver,
        private DateTime $date,
        private Uid $idEncoder,
        private StoreManagerInterface $storeManager,
        private Banner\CollectionFactory $factory,
    ) {}

    public function getImages(SliderInterface $slider): array
    {
        $options = $slider->getOptionsList();
        $jpg = isset($options['jpg']) && $options['jpg'] === true;
        $width = $this->getIntegerOption($options, 'width');
        $height = $this->getIntegerOption($options, 'height');
        $widthS = $this->getIntegerOption($options, 'width_small');
        $heightS = $this->getIntegerOption($options, 'height_small');

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
            $item = $entity->getData();
            if ($item['image'] === null) {
                continue;
            }

            try {
                $item['uid'] = $this->idEncoder->encode((string) $entity->getId());
                $item['image_formats'] = $this->buildImageFormats($options, $item['image'], $item['small_image'] ?? null);
                $defaultImg = $this->service->execute(
                    imagePath: $item['image'],
                    width: $width,
                    height: $height,
                    ext: $jpg ? 'jpg' : null,
                );
                $defaultSmallImg = $item['small_image'] ? $this->service->execute(
                    imagePath: $item['small_image'],
                    width: $widthS,
                    height: $heightS,
                    ext: $jpg ? 'jpg' : null,
                ) : null;
                $item['default'] = $defaultImg['url'];
                $item['image'] = $item['default'];
                $item['small_image'] = $defaultSmallImg['url'] ?? null;
                $result[] = $item;
            } catch (LocalizedException) {
                continue;
            }
        }

        return $this->linkResolver->addLinks(
            $result,
            (int) $this->storeManager->getStore()->getId(),
        );
    }

    private function getIntegerOption(array $options, string $key): ?int
    {
        return isset($options[$key]) && $options[$key] !== null ? (int) $options[$key] : null;
    }

    private function buildImageFormats(array $options, string $image, ?string $smallImage = null): array
    {
        $width = $this->getIntegerOption($options, 'width');
        $height = $this->getIntegerOption($options, 'height');
        $widthS = $this->getIntegerOption($options, 'width_small');
        $heightS = $this->getIntegerOption($options, 'height_small');
        $result = [];

        foreach (['avif', 'webp', 'jpg'] as $ext) {
            if (!isset($options[$ext]) || $options[$ext] !== true) {
                continue;
            }
            $result[$ext]['image'] = $this->service->execute(
                imagePath: $image,
                width: $width,
                height: $height,
                ext: $ext,
            );

            if ($smallImage === null || $widthS === null) {
                continue;
            }
            $result[$ext]['small_image'] = $this->service->execute(
                imagePath: $smallImage,
                width: $widthS,
                height: $heightS,
                ext: $ext,
            );
        }

        return $result;
    }
}
