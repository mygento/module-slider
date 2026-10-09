<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mygento\Slider\Model\DataBuilder\CategorySlider;
use Psr\Log\LoggerInterface;

class CategoryProductSlider implements ResolverInterface
{
    public function __construct(
        private CategorySlider $categorySliderBuilder,
        private LoggerInterface $logger,
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
        try {
            $slider = $this->categorySliderBuilder->resolverSlider($value);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            throw new GraphQlNoSuchEntityException(__('Something went wrong while trying to resolve the slider.'));
        }

        return $slider;
    }
}
