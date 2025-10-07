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

        $allAttributes = $this->getAllAttributesToTranslate($storeToTranslate);

        foreach ($allAttributes as $attribute) {
            $origLangOptions = $this->getOrigLangLabels($attribute);

            foreach ($origLangOptions as $optionValue => $origLabel) {
                if (empty(trim($origLabel))) {
                    continue;
                }

                $allStoreLabels = $this->collectExistingStoreLabels($attribute, $optionValue);
                $hasNewTranslations = false;

                foreach ($storeToTranslate as $storeId => $storeName) {
                    $targetLanguage = $this->moduleConfig->getDestinationLanguage($storeId);
                    $sourceLanguage = $this->moduleConfig->getSourceLanguage();

                    if ($sourceLanguage === $targetLanguage) {
                        continue;
                    }

                    if (!$this->shouldTranslateAttributeForStore($attribute, $storeId)) {
                        continue;
                    }

                    $existingLabel = $this->getExistingLabelForStore($attribute, $optionValue, $storeId);

                    if (!empty($existingLabel) && $existingLabel !== $origLabel) {
                        $allStoreLabels[$storeId] = $existingLabel;
                        continue;
                    }

                    try {
                        $translatedLabel = $this->translator->translate($origLabel, $targetLanguage, $sourceLanguage);
                        $allStoreLabels[$storeId] = $translatedLabel;
                        $hasNewTranslations = true;
                    } catch (Exception $e) {
                        $this->logger->debug('Error when translating the option');
                        $this->logger->debug('Attribute: ' . $attribute->getAttributeCode());
                        $this->logger->debug('Option: ' . $origLabel);
                        $this->logger->debug('Store ID: ' . $storeId);
                        $this->logger->debug($e->getMessage());
                        $this->logger->debug('-------------------------');
                    }
                }

                if ($hasNewTranslations) {
                    $this->saveAllStoreLabels($attribute, $optionValue, $allStoreLabels, $origLabel);
                }
            }
        }
    }

    protected function getAllAttributesToTranslate(array $storeToTranslate): array
    {
        $allAttributeCodes = [];

        foreach ($storeToTranslate as $storeId => $storeName) {
            $attributeCodes = $this->moduleConfig->getProductSelectAttributeToTranslate($storeId);
            $allAttributeCodes = array_merge($allAttributeCodes, $attributeCodes);
        }

        $allAttributeCodes = array_unique($allAttributeCodes);

        if (empty($allAttributeCodes)) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(AttributeInterface::ATTRIBUTE_CODE, $allAttributeCodes, 'in')
            ->create();

        return $this->attributeRepository->getList(ProductModel::ENTITY, $searchCriteria)->getItems();
    }

    protected function shouldTranslateAttributeForStore(\Magento\Eav\Api\Data\AttributeInterface $attribute, int $storeId): bool
    {
        $attributeCodes = $this->moduleConfig->getProductSelectAttributeToTranslate($storeId);
        return in_array($attribute->getAttributeCode(), $attributeCodes);
    }

    protected function getExistingLabelForStore(\Magento\Eav\Api\Data\AttributeInterface $attribute, $optionValue, int $storeId): ?string
    {
        $options = $this->getAttributeOptions($attribute, $storeId);

        foreach ($options as $option) {
            if ($option->getValue() == $optionValue) {
                return $option->getLabel();
            }
        }

        return null;
    }

    protected function collectExistingStoreLabels(\Magento\Eav\Api\Data\AttributeInterface $attribute, $optionValue): array
    {
        $storeLabels = [];
        $stores = $this->storeManager->getStores(true); // Include admin store

        foreach ($stores as $store) {
            $storeId = (int)$store->getId();
            $label = $this->getExistingLabelForStore($attribute, $optionValue, $storeId);

            if (!empty($label)) {
                $storeLabels[$storeId] = $label;
            }
        }

        return $storeLabels;
    }

    protected function saveAllStoreLabels(\Magento\Eav\Api\Data\AttributeInterface $attribute, $optionValue, array $allStoreLabels, string $origLabel): void
    {
        $adminOptions = $this->getAttributeOptions($attribute, 0);
        $optionToUpdate = null;

        foreach ($adminOptions as $option) {
            if ($option->getValue() == $optionValue) {
                $optionToUpdate = $option;
                break;
            }
        }

        if (!$optionToUpdate) {
            $this->logger->debug('Option not found for value: ' . $optionValue);
            return;
        }

        $storeLabelsArray = [];
        foreach ($allStoreLabels as $storeId => $label) {
            if (!empty($label)) {
                $labelObject = $this->attributeOptionLabelInterface->create();
                $labelObject->setStoreId($storeId)->setLabel($label);
                $storeLabelsArray[] = $labelObject;
            }
        }

        $optionToUpdate->setStoreLabels($storeLabelsArray);

        try {
            $this->optionUpdate->update(
                ProductModel::ENTITY,
                $attribute->getAttributeCode(),
                $optionValue,
                $optionToUpdate
            );
        } catch (InputException|NoSuchEntityException|StateException $e) {
            $this->logger->debug('Error when saving translated options');
            $this->logger->debug('Attribute: ' . $attribute->getAttributeCode());
            $this->logger->debug('Option: ' . $origLabel);
            $this->logger->debug($e->getMessage());
            $this->logger->debug('-------------------------');
        }
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
