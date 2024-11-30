<?php

namespace MageOS\AutomaticTranslation\Service;

use MageOS\AutomaticTranslation\Api\TranslateProductsInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service as ServiceHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Api\ProductRepositoryInterface;
use MageOS\AutomaticTranslation\Api\AttributeProviderInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\DataObject;
use Magento\Catalog\Api\Data\ProductInterface;
use MageOS\AutomaticTranslation\Api\ProductTranslatorInterface;

/**
 * Class TranslateProducts
 */
class TranslateProducts implements TranslateProductsInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;
    /**
     * @var ServiceHelper
     */
    protected ServiceHelper $serviceHelper;
    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;
    /**
     * @var FilterBuilder
     */
    protected FilterBuilder $filterBuilder;
    /**
     * @var ProductTranslatorInterface
     */
    protected ProductTranslatorInterface $productTranslator;

    /**
     * TranslateProducts constructor.
     * @param StoreManagerInterface $storeManager
     * @param ModuleConfig $moduleConfig
     * @param ServiceHelper $serviceHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param FilterBuilder $filterBuilder
     * @param ProductTranslatorInterface $productTranslator
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ModuleConfig $moduleConfig,
        ServiceHelper $serviceHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        FilterBuilder $filterBuilder,
        ProductTranslatorInterface $productTranslator
    ) {
        $this->storeManager = $storeManager;
        $this->moduleConfig = $moduleConfig;
        $this->serviceHelper = $serviceHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->filterBuilder = $filterBuilder;
        $this->productTranslator = $productTranslator;
    }

    /**
     * @return void
     */
    public function translateProducts(): void
    {
        $storeToTranslate = $this->serviceHelper->getStoresToTranslate();

        foreach ($storeToTranslate as $storeId => $storeName) {
            $productsToTranslate = $this->getProductsToTranslate($storeId);
            $targetLanguage = $this->moduleConfig->getDestinationLanguage($storeId);
            $sourceLanguage = $this->moduleConfig->getSourceLanguage();

            /** @var $product DataObject|ProductInterface */
            foreach ($productsToTranslate as $product) {
                $this->productTranslator->translateProduct($product, $targetLanguage, $sourceLanguage, $storeName, $storeId);
            }
        }
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function getProductsToTranslate($storeId): array
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
     * @return void
     */
    protected function filterByStatus(SearchCriteriaBuilder $searchCriteriaBuilder, int $storeId = 0)
    {
        if (!$this->moduleConfig->translateDisabledProducts($storeId)) {
            $searchCriteriaBuilder->addFilter(ProductAttributeInterface::CODE_STATUS, Status::STATUS_ENABLED);
        }
    }

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param int $storeId
     * @param bool $getExpired
     * @return void
     */
    protected function filterByRetranslationDate(SearchCriteriaBuilder $searchCriteriaBuilder, int $storeId = 0, bool $getExpired = true): void
    {
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
            $searchCriteriaBuilder->addFilter(AttributeProviderInterface::LAST_TRANSLATION, $translationExpirationDate, 'gt');
        }
    }

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @return void
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
