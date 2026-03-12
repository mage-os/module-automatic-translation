<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Model\Translator;

use Magento\Framework\ObjectManagerInterface;
use MageOS\AutomaticTranslation\Api\TranslatorInterface;

class TranslatorFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        protected ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * @param string $instanceName
     * @return TranslatorInterface
     */
    public function create(string $instanceName): TranslatorInterface
    {
        return $this->objectManager->create($instanceName);
    }
}
