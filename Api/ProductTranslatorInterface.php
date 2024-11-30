<?php

namespace MageOS\AutomaticTranslation\Api;

use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Interface ProductTranslatorInterface
 */
interface ProductTranslatorInterface
{
    /**
     * @param ProductInterface $product
     * @param string $targetLanguage
     * @param string $sourceLanguage
     * @param string $storeName
     * @param int $storeId
     * @return void
     */
    public function translateProduct(
        ProductInterface $product,
        string $targetLanguage,
        string $sourceLanguage,
        string $storeName = 'Default Store View',
        int $storeId = 0
    ): void;
}
