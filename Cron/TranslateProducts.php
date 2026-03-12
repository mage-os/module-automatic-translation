<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Cron;

use MageOS\AutomaticTranslation\Api\TranslateProductsInterface;

class TranslateProducts
{
    /**
     * @param TranslateProductsInterface $translateProducts
     */
    public function __construct(
        protected TranslateProductsInterface $translateProducts
    ) {
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->translateProducts->translateProducts();
    }
}
