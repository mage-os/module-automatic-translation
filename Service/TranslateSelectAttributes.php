<?php

namespace MageOS\AutomaticTranslation\Service;

use MageOS\AutomaticTranslation\Api\TranslateSelectAttributesInterface;
use MageOS\AutomaticTranslation\Helper\Service as ServiceHelper;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\AttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface as AttributeRepository;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use Magento\Eav\Api\AttributeOptionUpdateInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;
use Exception;

/**
 * Class TranslateSelectAttributes
 */
class TranslateSelectAttributes implements TranslateSelectAttributesInterface
{
    /**
     * @var ServiceHelper
     */
    protected ServiceHelper $serviceHelper;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;
    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    /**
     * @var AttributeRepository
     */
    protected AttributeRepository $attributeRepository;
    /**
     * @var AttributeFrontendLabelInterface
     */
    protected AttributeFrontendLabelInterface $frontendLabel;
    /**
     * @var TranslatorInterface
     */
    protected TranslatorInterface $translator;
    /**
     * @var AttributeOptionUpdateInterface
     */
    protected AttributeOptionUpdateInterface $optionUpdate;
    /**
     * @var AttributeOptionLabelInterfaceFactory
     */
    protected AttributeOptionLabelInterfaceFactory $attributeOptionLabelInterface;
    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * TranslateSelectAttributes constructor.
     * @param ServiceHelper $serviceHelper
     * @param StoreManagerInterface $storeManager
     * @param ModuleConfig $moduleConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeRepository $attributeRepository
     * @param AttributeFrontendLabelInterface $frontendLabel
     * @param TranslatorInterface $translator
     * @param AttributeOptionUpdateInterface $optionUpdate
     * @param AttributeOptionLabelInterfaceFactory $attributeOptionLabelInterface
     * @param Logger $logger
     */
    public function __construct(
        ServiceHelper $serviceHelper,
        StoreManagerInterface $storeManager,
        ModuleConfig $moduleConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepository $attributeRepository,
        AttributeFrontendLabelInterface $frontendLabel,
        TranslatorInterface $translator,
        AttributeOptionUpdateInterface $optionUpdate,
        AttributeOptionLabelInterfaceFactory $attributeOptionLabelInterface,
        Logger $logger
    ) {
        $this->serviceHelper = $serviceHelper;
        $this->storeManager = $storeManager;
        $this->moduleConfig = $moduleConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->frontendLabel = $frontendLabel;
        $this->translator = $translator;
        $this->optionUpdate = $optionUpdate;
        $this->attributeOptionLabelInterface = $attributeOptionLabelInterface;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function translateOptions(): void
    {
        $storeToTranslate = $this->serviceHelper->getStoresToTranslate();

        foreach ($storeToTranslate as $storeId => $storeName) {
            $attributesToTranslate = $this->getAttributeToTranslate($storeId);

            foreach ($attributesToTranslate as $attribute) {
                $origLangOptions = $this->getOrigLangLabels($attribute);
                $attributeOptions = $this->getAttributeOptions($attribute, $storeId);

                foreach ($attributeOptions as $option) {
                    if (empty($option->getValue())) {
                        continue;
                    }

                    $origLangOptionLabel = $origLangOptions[$option->getValue()];
                    $optionLabel = $option->getLabel();

                    if (empty($optionLabel) || $optionLabel === $origLangOptionLabel) {
                        $targetLanguage = $this->moduleConfig->getDestinationLanguage($storeId);
                        $sourceLanguage = $this->moduleConfig->getSourceLanguage();

                        if ($sourceLanguage === $targetLanguage || empty(trim($origLangOptionLabel))) {
                            continue;
                        }

                        try {
                            $translatedLabel = $this->translator->translate($origLangOptionLabel, $targetLanguage, $sourceLanguage);
                        } catch (Exception $e) {
                            $this->logger->debug('Error when translating the option');
                            $this->logger->debug('Attribute: ' . $attribute->getAttributeCode());
                            $this->logger->debug('Option: ' . $origLangOptionLabel);
                            $this->logger->debug($e->getMessage());
                            $this->logger->debug('-------------------------');

                            continue;
                        }

                        $labelObject = $this->attributeOptionLabelInterface->create();
                        $labelObject->setStoreId($storeId)
                            ->setLabel($translatedLabel);

                        $option->setStoreLabels([$labelObject]);

                        try {
                            $this->optionUpdate->update(
                                ProductModel::ENTITY,
                                $attribute->getAttributeCode(),
                                $option->getValue(),
                                $option
                            );
                        } catch (InputException|NoSuchEntityException|StateException $e) {
                            $this->logger->debug('Error when saving translated the option');
                            $this->logger->debug('Attribute: ' . $attribute->getAttributeCode());
                            $this->logger->debug('Option: ' . $origLangOptionLabel);
                            $this->logger->debug($e->getMessage());
                            $this->logger->debug('-------------------------');
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $storeId
     * @return \Magento\Eav\Api\Data\AttributeInterface[]
     */
    protected function getAttributeToTranslate($storeId): array
    {
        $attributeCodes = $this->moduleConfig->getProductSelectAttributeToTranslate($storeId);
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(AttributeInterface::ATTRIBUTE_CODE, $attributeCodes, 'in')
            ->create();
        $attributes = $this->attributeRepository->getList(ProductModel::ENTITY, $searchCriteria)->getItems();

        return $attributes;
    }

    /**
     * @param \Magento\Eav\Api\Data\AttributeInterface $attribute
     * @param int $storeId
     * @return \Magento\Eav\Api\Data\AttributeOptionInterface[]|null
     */
    protected function getAttributeOptions(\Magento\Eav\Api\Data\AttributeInterface $attribute, int $storeId = 0): ?array
    {
        $this->storeManager->setCurrentStore($storeId);
        return $attribute->getOptions();
    }

    /**
     * @param \Magento\Eav\Api\Data\AttributeInterface $attribute
     * @return array
     */
    protected function getOrigLangLabels(\Magento\Eav\Api\Data\AttributeInterface $attribute): array
    {
        $options = $this->getAttributeOptions($attribute);

        $optionArray = [];
        foreach ($options as $option) {
            if (empty($option->getValue())) {
                continue;
            }

            $optionArray[$option->getValue()] = $option->getLabel();
        }

        return $optionArray;
    }
}
