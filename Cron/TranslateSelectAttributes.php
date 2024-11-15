<?php

namespace MageOS\AutomaticTranslation\Cron;

use MageOS\AutomaticTranslation\Api\TranslateSelectAttributesInterface;

class TranslateSelectAttributes
{
    /**
     * @var TranslateSelectAttributesInterface
     */
    protected TranslateSelectAttributesInterface $translateSelectAttributes;

    /**
     * @param TranslateSelectAttributesInterface $translateSelectAttributes
     */
    public function __construct(
        TranslateSelectAttributesInterface $translateSelectAttributes
    ) {
        $this->translateSelectAttributes = $translateSelectAttributes;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->translateSelectAttributes->translateOptions();
    }
}
