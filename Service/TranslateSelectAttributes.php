<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Service;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\Eav\Api\AttributeOptionUpdateInterface;
use Magento\Eav\Api\AttributeRepositoryInterface as AttributeRepository;
use Magento\Eav\Api\Data\AttributeInterface as EavAttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\AutomaticTranslation\Api\TranslateSelectAttributesInterface;
use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service as ServiceHelper;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Exception;

class TranslateSelectAttributes implements TranslateSelectAttributesInterface
{
    /**
     * @param ServiceHelper $serviceHelper
     * @param StoreManagerInterface $storeManager
     * @param ModuleConfig $moduleConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeRepository $attributeRepository
     * @param TranslatorInterface $translator
     * @param AttributeOptionUpdateInterface $optionUpdate
     * @param AttributeOptionLabelInterfaceFactory $attributeOptionLabelInterface
     * @param Logger $logger
     */
    public function __construct(
        protected ServiceHelper $serviceHelper,
        protected StoreManagerInterface $storeManager,
        protected ModuleConfig $moduleConfig,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected AttributeRepository $attributeRepository,
        protected TranslatorInterface $translator,
        protected AttributeOptionUpdateInterface $optionUpdate,
        protected AttributeOptionLabelInterfaceFactory $attributeOptionLabelInterface,
        protected Logger $logger
    ) {
    }

    /**
     * @return void
     */
    public function translateOptions(): void
    {
        $storeToTranslate = $this->serviceHelper->getStoresToTranslate();

        foreach ($storeToTranslate as $storeId => $storeName) {
            $targetLanguage = $this->moduleConfig->getDestinationLanguage($storeId);
            $sourceLanguage = $this->moduleConfig->getSourceLanguage();

            if ($sourceLanguage === $targetLanguage) {
                continue;
            }

            $attributesToTranslate = $this->getAttributeToTranslate($storeId);

            foreach ($attributesToTranslate as $attribute) {
                $origLangOptions = $this->getOrigLangLabels($attribute);
                $attributeOptions = $this->getAttributeOptions($attribute, $storeId);

                foreach ($attributeOptions as $option) {
                    if (empty($option->getValue())) {
                        continue;
                    }

                    $origLangOptionLabel = $origLangOptions[(string)$option->getValue()] ?? '';

                    if (empty(trim($origLangOptionLabel))) {
                        continue;
                    }

                    $optionLabel = $option->getLabel();

                    if (!empty($optionLabel) && $optionLabel !== $origLangOptionLabel) {
                        continue;
                    }

                    try {
                        $translatedLabel = $this->translator->translate(
                            $origLangOptionLabel,
                            $targetLanguage,
                            $sourceLanguage
                        );
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
                            (int)$option->getValue(),
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

    /**
     * @param int $storeId
     * @return EavAttributeInterface[]
     */
    protected function getAttributeToTranslate(int $storeId): array
    {
        $attributeCodes = $this->moduleConfig->getProductSelectAttributeToTranslate($storeId);
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            AttributeInterface::ATTRIBUTE_CODE,
            $attributeCodes,
            'in'
        )
            ->create();

        return $this->attributeRepository->getList(ProductModel::ENTITY, $searchCriteria)->getItems();
    }

    /**
     * @param EavAttributeInterface $attribute
     * @param int $storeId
     * @return AttributeOptionInterface[]|null
     */
    protected function getAttributeOptions(
        EavAttributeInterface $attribute,
        int $storeId = 0
    ): ?array {
        $this->storeManager->setCurrentStore($storeId);
        return $attribute->getOptions();
    }

    /**
     * @param EavAttributeInterface $attribute
     * @return array
     */
    protected function getOrigLangLabels(EavAttributeInterface $attribute): array
    {
        $options = $this->getAttributeOptions($attribute);

        $optionArray = [];
        foreach ($options as $option) {
            if (empty($option->getValue())) {
                continue;
            }

            $optionArray[(string)$option->getValue()] = $option->getLabel();
        }

        return $optionArray;
    }
}
