<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mygento\Slider\Api\SliderRepositoryInterface;
use Mygento\Slider\Model\DataBuilder\BannerDataBuilder;

class Slider implements ResolverInterface
{
    public function __construct(
        private SliderRepositoryInterface $sliderRepository,
        private BannerDataBuilder $bannerDataBuilder,
    ) {}

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ) {
        $identity = $args['banner_slider'] ?? $value['banner_slider'] ?? null;

        if (!$identity) {
            throw new GraphQlNoSuchEntityException(__('Slider Identity arg is required'));
        }

        try {
            $slider = $this->sliderRepository->getByIdentity($identity);
        } catch (\Exception) {
            throw new GraphQlNoSuchEntityException(__('Slider not found or disabled'));
        }
        if (!$slider->isActive()) {
            throw new GraphQlNoSuchEntityException(__('Slider not found or disabled '));
        }

        $banners = $this->bannerDataBuilder->getImages($slider);

        return [
            'title' => $slider->getTitle(),
            'identity' => $slider->getIdentity(),
            'options' => $slider->getOptionsList(),
            'content' => $slider->getContent(),
            'banners' => $banners,
        ];
    }
}
