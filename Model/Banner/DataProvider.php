<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Model\Banner;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;
use Mygento\Slider\Model\EntityResolverPool;
use Mygento\Slider\Model\FileInfo;
use Mygento\Slider\Model\ResourceModel\Banner\Collection;
use Mygento\Slider\Model\ResourceModel\Banner\CollectionFactory;

class DataProvider extends ModifierPoolDataProvider
{
    /** @var Collection */
    protected $collection;

    private DataPersistorInterface $dataPersistor;
    private array $loadedData = [];

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private EntityResolverPool $poolResolver,
        private FileInfo $fileInfo,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        array $meta = [],
        array $data = [],
        ?PoolInterface $pool = null,
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);

        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        $idsByType = $this->collectIdsByType($items);
        $resolved = $this->resolveEntities($idsByType);
        foreach ($items as $model) {
            $this->loadedData[$model->getId()] = $this->prepareData($model->getData());
            $type = $model->getEntityType() ?? null;
            $identifier = $model->getEntityIdentifier() ?? null;
            $data['entity_label'] = $resolved[$type][$identifier] ?? $identifier;
        }

        $data = $this->dataPersistor->get('slider_banner');
        if (!empty($data)) {
            $model = $this->collection->getNewEmptyItem();

            $model->setData($this->prepareData($data));

            $this->loadedData[$model->getId()] = $model->getData();
            $this->dataPersistor->clear('slider_banner');
        }

        return $this->loadedData;
    }

    private function prepareData(array $data): array
    {
        $data['image'] = $this->getImageData($data, 'image');
        $data['small_image'] = $this->getImageData($data, 'small_image');

        return $data;
    }

    private function getImageData(array $data, string $key): ?array
    {
        $imageFileName = $data[$key] ?? null;
        if (!$imageFileName) {
            return null;
        }
        if (is_array($imageFileName) && isset($imageFileName[0]['name']) && $imageFileName[0]['name']) {
            $imageFileName = $imageFileName[0]['name'];
        }
        if (is_array($imageFileName)) {
            return null;
        }

        $imageFilePath = 'mygentoslider/banner/' . $imageFileName;
        $result = null;
        if (!$this->fileInfo->isExist($imageFilePath)) {
            return $result;
        }

        $stat = $this->fileInfo->getStat($imageFilePath);
        $mime = $this->fileInfo->getMimeType($imageFilePath);

        return [
            [
                'name' => $imageFileName,
                'url' => $this->fileInfo->getUrl($imageFilePath),
                'size' => $stat['size'],
                'type' => $mime,
            ],
        ];
    }

    /**
     * @param array<int, array{
     *     entity_type?: string|null,
     *     entity_identifier?: int|string|null,
     * }> $items
     *
     * @return array<string, list<string>>
     */
    private function collectIdsByType(array $items): array
    {
        $idsByType = [];

        foreach ($items as $item) {
            $type = $item['entity_type'] ?? null;
            $identifier = $item['entity_identifier'] ?? null;

            if (!$type || !$identifier) {
                continue;
            }

            $idsByType[$type][] = (string) $identifier;
        }

        return $idsByType;
    }

    /**
     * @param array<string, list<string>> $idsByType
     *
     * @return array<string, array<string, array{
     *     entity_identifier: int|string,
     *     url?: string|null,
     * }>>
     */
    private function resolveEntities(array $idsByType): array
    {
        $resolved = [];

        foreach ($idsByType as $type => $ids) {
            $resolver = $this->poolResolver->get($type);

            if (!$resolver) {
                continue;
            }

            $resolved[$type] = $resolver->resolveName(
                array_unique($ids),
            );
        }

        return $resolved;
    }
}
