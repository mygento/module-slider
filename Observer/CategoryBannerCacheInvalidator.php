<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

declare(strict_types=1);

namespace Mygento\Slider\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\PageCache\Model\Cache\Type;
use Mygento\Slider\Model\Cache\CategoryBannerIdentity;

class CategoryBannerCacheInvalidator implements ObserverInterface
{
    public function __construct(private Type $cache) {}

    public function execute(Observer $observer): void
    {
        $categoryId =  $observer->getCategory()?->getId();
        $this->cache->clean(
            \Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            [CategoryBannerIdentity::CACHE_TAG . '_' . $categoryId],
        );
    }
}
