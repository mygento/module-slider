<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mygento\Slider\Api\BannerRepositoryInterface;
use Mygento\Slider\Api\SliderRepositoryInterface;
use Mygento\Slider\Model\SliderOptions;

class Slider implements ResolverInterface
{
    public function __construct(
        private SliderRepositoryInterface $sliderRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private FilterBuilder $filterBuilder,
        private BannerRepositoryInterface $bannerRepository,
        private SliderOptions $sliderOptions,
    ) {}

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null,
    ) {
        $identity = $args['identity'] ?? null;

        if (!$identity) {
            throw new GraphQlNoSuchEntityException(__('Slider Identity arg is required'));
        }

        try {
            $slider = $this->sliderRepository->getByIdentity($identity);
            $bannerItems = [];
            $banners = $slider->getBannerIds();
            if ($banners) {
                $filter = $this->filterBuilder
                    ->setField('id')
                    ->setValue(implode(',', array_keys($banners)))
                    ->setConditionType('in')
                    ->create();

                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilters([$filter])
                    ->create();
                $searchResult = $this->bannerRepository->getList($searchCriteria);
                $bannerEntities =  $searchResult->getItems();
                $bannerItems = [];
                foreach ($banners as $id => $data) {
                    if (!isset($bannerEntities[$id])) {
                        continue;
                    }
                    $bannerItem = $bannerEntities[$id]->getData();
                    $bannerItem['position'] = $data['position'];
                    $bannerItems[] = $bannerItem;
                }
            }

            return [
                'title' => $slider->getTitle(),
                'identity' => $slider->getIdentity(),
                'options' => $this->sliderOptions->getOptions($slider->getOptions()),
                'content' => $slider->getContent(),
                'is_active' => $slider->getIsActive(),
                'banners' => $bannerItems,
            ];
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }
    }
}
