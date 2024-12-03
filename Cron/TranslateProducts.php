<?php

namespace MageOS\AutomaticTranslation\Cron;

use MageOS\AutomaticTranslation\Api\TranslateProductsInterface;

/**
 * Class TranslateProducts
 */
class TranslateProducts
{
    /**
     * @var TranslateProductsInterface
     */
    protected TranslateProductsInterface $translateProducts;

    /**
     * TranslateProducts constructor.
     * @param TranslateProductsInterface $translateProducts
     */
    public function __construct(
        TranslateProductsInterface $translateProducts
    ) {
        $this->translateProducts = $translateProducts;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->translateProducts->translateProducts();
    }
}
