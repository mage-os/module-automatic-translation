<?php

namespace MageOS\AutomaticTranslation\Cron;

use MageOS\AutomaticTranslation\Api\TranslateProductsInterface;

class TranslateProducts
{
    /**
     * @var TranslateProductsInterface
     */
    protected TranslateProductsInterface $translateProducts;

    /**
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
