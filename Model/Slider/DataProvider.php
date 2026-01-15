<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Model\Slider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;
use Mygento\Slider\Api\Data\BannerInterface;
use Mygento\Slider\Api\Data\SliderInterface;
use Mygento\Slider\Model\Resizer;
use Mygento\Slider\Model\ResourceModel\Banner;
use Mygento\Slider\Model\ResourceModel\Slider;

class DataProvider extends ModifierPoolDataProvider
{
    /** @var Slider\Collection */
    protected $collection;

    private DataPersistorInterface $dataPersistor;
    private array $loadedData = [];

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private Resizer $service,
        private StoreManagerInterface $storeManager,
        private Banner\CollectionFactory $bannerCollectionFactory,
        Slider\CollectionFactory $sliderCollectionFactory,
        DataPersistorInterface $dataPersistor,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        array $meta = [],
        array $data = [],
        ?PoolInterface $pool = null,
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);

        $this->collection = $sliderCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $model) {
            $this->loadedData[$model->getId()] = $this->prepareData($model->getData(), $model);
        }
        $data = $this->dataPersistor->get('slider_slider');
        if (!empty($data)) {
            $model = $this->collection->getNewEmptyItem();
            $model->setData($this->prepareData($data, $model));
            $this->loadedData[$model->getId()] = $model->getData();
            $this->dataPersistor->clear('slider_slider');
        }

        return $this->loadedData;
    }

    private function prepareData(array $data, SliderInterface $model): array
    {
        $collection = $this->bannerCollectionFactory->create();
        $collection->fetchItemsWithPositionBySlider($data['id']);

        $result = [];
        /** @var BannerInterface $item */
        foreach ($collection as $item) {
            $banner = [
                'id' => (string) $item->getId(),
                'name' => $item->getName(),
                'from_date' => $item->getFromDate(),
                'to_date' => $item->getToDate(),
                'is_active' => $item->isActive(),
                'position' => (int) $item->getData('position'),
            ];

            try {
                $banner['image'] = $this->service->resizeAndConvert(
                    $item->getImage(),
                    null,
                    48,
                    48,
                );
                if ($item->getSmallImage()) {
                    $banner['small_image'] = $this->service->resizeAndConvert(
                        $item->getSmallImage(),
                        null,
                        48,
                        48,
                    );
                }
            } catch (LocalizedException) {
                $banner['image'] = $this->getUrl($item->getData(BannerInterface::IMAGE));
                $banner['small_image'] = $this->getUrl($item->getData(BannerInterface::SMALL_IMAGE));
            }

            $result[] = $banner;
        }

        $data['slider_items'] = $result;
        $data['options'] = $model->getOptionsList();

        return $data;
    }

    private function getUrl(string $path): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();

        return $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path;
    }
}
