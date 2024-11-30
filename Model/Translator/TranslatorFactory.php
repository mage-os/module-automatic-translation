<?php

namespace MageOS\AutomaticTranslation\Model\Translator;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class TranslatorFactory
 */
class TranslatorFactory
{
    /**
     * @var ObjectManagerInterface|null
     */
    protected ?ObjectManagerInterface $objectManager = null;

    /**
     * TranslatorFactory constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param $instanceName
     * @return mixed
     */
    public function create($instanceName)
    {
        return $this->objectManager->create($instanceName);
    }
}
