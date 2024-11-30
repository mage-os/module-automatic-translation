<?php

namespace MageOS\AutomaticTranslation\Cron;

use MageOS\AutomaticTranslation\Api\TranslateSelectAttributesInterface;

/**
 * Class TranslateSelectAttributes
 */
class TranslateSelectAttributes
{
    /**
     * @var TranslateSelectAttributesInterface
     */
    protected TranslateSelectAttributesInterface $translateSelectAttributes;

    /**
     * TranslateSelectAttributes constructor.
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
