<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Cron;

use MageOS\AutomaticTranslation\Api\TranslateSelectAttributesInterface;

class TranslateSelectAttributes
{
    /**
     * @param TranslateSelectAttributesInterface $translateSelectAttributes
     */
    public function __construct(
        protected TranslateSelectAttributesInterface $translateSelectAttributes
    ) {
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->translateSelectAttributes->translateOptions();
    }
}
