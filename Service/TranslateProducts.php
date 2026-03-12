<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Service;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\AutomaticTranslation\Api\AttributeProviderInterface;
use MageOS\AutomaticTranslation\Api\ProductTranslatorInterface;
use MageOS\AutomaticTranslation\Api\TranslateProductsInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service as ServiceHelper;

class TranslateProducts implements TranslateProductsInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param ModuleConfig $moduleConfig
     * @param ServiceHelper $serviceHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param FilterBuilder $filterBuilder
     * @param ProductTranslatorInterface $productTranslator
     */
    public function __construct(
        protected StoreManagerInterface $storeManager,
        protected ModuleConfig $moduleConfig,
        protected ServiceHelper $serviceHelper,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected ProductRepositoryInterface $productRepository,
        protected FilterBuilder $filterBuilder,
        protected ProductTranslatorInterface $productTranslator
    ) {
    }

    /**
     * @return void
     */
    public function translateProducts(): void
    {
        $storeToTranslate = $this->serviceHelper->getStoresToTranslate();
        $sourceLanguage = $this->moduleConfig->getSourceLanguage();

        foreach ($storeToTranslate as $storeId => $storeName) {
            $targetLanguage = $this->moduleConfig->getDestinationLanguage($storeId);
            $productsToTranslate = $this->getProductsToTranslate($storeId);

            /** @var $product DataObject|ProductInterface */
            foreach ($productsToTranslate as $product) {
                $this->productTranslator->translateProduct(
                    $product,
                    $targetLanguage,
                    $sourceLanguage,
                    $storeName,
                    $storeId
                );
            }
        }
    }

    /**
     * @param int $storeId
     * @return array
     */
    protected function getProductsToTranslate(int $storeId): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilder->addFilter(ProductModel::STORE_ID, $storeId);

        $this->filterByStatus($searchCriteriaBuilder, $storeId);

        if ($this->moduleConfig->isEnablePeriodicRetranslation()) {
            $searchCriteriaBuilder2 = clone $searchCriteriaBuilder;

            $this->filterByRetranslationDate($searchCriteriaBuilder, $storeId);
            $searchCriteria = $searchCriteriaBuilder->create();
            $expiredTranslationProduct = $this->productRepository->getList($searchCriteria)->getItems();

            $this->filterByRetranslationDate($searchCriteriaBuilder2, $storeId, false);
            $this->filterBySkipTranslation($searchCriteriaBuilder2);
            $searchCriteria2 = $searchCriteriaBuilder2->create();
            $unexpiredButUnskipTranslationProduct = $this->productRepository->getList($searchCriteria2)->getItems();

            $products = array_merge($expiredTranslationProduct, $unexpiredButUnskipTranslationProduct);
        } else {
            $this->filterBySkipTranslation($searchCriteriaBuilder);
            $searchCriteria = $searchCriteriaBuilder->create();
            $products = $this->productRepository->getList($searchCriteria)->getItems();
        }

        return $products;
    }

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param int $storeId
     */
    protected function filterByStatus(SearchCriteriaBuilder $searchCriteriaBuilder, int $storeId = 0): void
    {
        if (!$this->moduleConfig->translateDisabledProducts($storeId)) {
            $searchCriteriaBuilder->addFilter(ProductAttributeInterface::CODE_STATUS, Status::STATUS_ENABLED);
        }
    }

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param int $storeId
     * @param bool $getExpired
     */
    protected function filterByRetranslationDate(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        int $storeId = 0,
        bool $getExpired = true
    ): void {
        $translationExpirationDate = $this->moduleConfig->getTranslationExpirationDate($storeId);

        if ($getExpired) {
            $searchCriteriaBuilder->addFilters([
                $this->filterBuilder->setField(AttributeProviderInterface::LAST_TRANSLATION)
                    ->setValue('')
                    ->setConditionType('null')
                    ->create(),
                $this->filterBuilder->setField(AttributeProviderInterface::LAST_TRANSLATION)
                    ->setValue($translationExpirationDate)
                    ->setConditionType('lteq')
                    ->create()
            ]);
        } else {
            $searchCriteriaBuilder->addFilter(
                AttributeProviderInterface::LAST_TRANSLATION,
                $translationExpirationDate,
                'gt'
            );
        }
    }

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected function filterBySkipTranslation(SearchCriteriaBuilder $searchCriteriaBuilder): void
    {
        $searchCriteriaBuilder->addFilters([
            $this->filterBuilder->setField(AttributeProviderInterface::SKIP_TRANSLATION)
                ->setValue(0)
                ->setConditionType('eq')
                ->create(),
            $this->filterBuilder->setField(AttributeProviderInterface::SKIP_TRANSLATION)
                ->setValue('')
                ->setConditionType('null')
                ->create(),
            $this->filterBuilder->setField(AttributeProviderInterface::SKIP_TRANSLATION)
                ->setValue(1)
                ->setConditionType('neq')
                ->create()
        ]);
    }
}
