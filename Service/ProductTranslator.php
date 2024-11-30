<?php

namespace MageOS\AutomaticTranslation\Service;

use MageOS\AutomaticTranslation\Api\ProductTranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service as ServiceHelper;
use MageOS\AutomaticTranslation\Api\AttributeProviderInterface;
use Magento\Framework\DataObject;
use Magento\Catalog\Api\Data\ProductInterface;
use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Psr\Log\LoggerInterface as Logger;
use Exception;

/**
 * Class ProductTranslator
 */
class ProductTranslator implements ProductTranslatorInterface
{
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;
    /**
     * @var ServiceHelper
     */
    protected ServiceHelper $serviceHelper;
    /**
     * @var TranslatorInterface
     */
    protected TranslatorInterface $translator;
    /**
     * @var ProductResource
     */
    protected ProductResource $productResource;
    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * ProductTranslator constructor.
     * @param ModuleConfig $moduleConfig
     * @param ServiceHelper $serviceHelper
     * @param TranslatorInterface $translator
     * @param ProductResource $productResource
     * @param Logger $logger
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        ServiceHelper $serviceHelper,
        TranslatorInterface $translator,
        ProductResource $productResource,
        Logger $logger
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->serviceHelper = $serviceHelper;
        $this->translator = $translator;
        $this->productResource = $productResource;
        $this->logger = $logger;
    }

    /**
     * @param ProductInterface $product
     * @param string $targetLanguage
     * @param string $sourceLanguage
     * @param string $storeName
     * @param int $storeId
     */
    public function translateProduct(ProductInterface $product, string $targetLanguage, string $sourceLanguage, string $storeName = 'Default Store View', int $storeId = 0): void
    {
        /** @var $product DataObject|ProductInterface */
        $attributesToTranslate = $this->moduleConfig->getProductTxtAttributeToTranslate($storeId);

        foreach ($attributesToTranslate as $attributeCode) {
            $textToTranslate = $product->getData($attributeCode);

            if (!empty($textToTranslate)) {
                try {
                    $parsedContent = $this->serviceHelper->parsePageBuilderHtmlBox($textToTranslate);

                    if (is_string($parsedContent)) {
                        $textTranslated = $this->translator->translate($textToTranslate, $targetLanguage, $sourceLanguage);
                    } else {
                        $textToTranslate = html_entity_decode(htmlspecialchars_decode($textToTranslate));
                        $textTranslated = $textToTranslate;

                        foreach ($parsedContent as $parsedString) {
                            $parsedString["translation"] = $this->translator->translate(
                                $parsedString["source"],
                                $targetLanguage
                            );

                            $textTranslated = str_replace($parsedString["source"], $parsedString["translation"], $textTranslated);
                        }

                        $textTranslated = $this->serviceHelper->encodePageBuilderHtmlBox($textTranslated);
                    }

                    if ($textToTranslate != $textTranslated) {
                        $product->setData($attributeCode, $textTranslated);
                        $this->productResource->saveAttribute($product, $attributeCode);
                    }
                } catch (Exception $e) {
                    $this->logger->debug('Error when translating the product');
                    $this->logger->debug('Product sku: ' . $product->getSku());
                    $this->logger->debug('Store: ' . $storeName . '(id ' . $storeId . ')');
                    $this->logger->debug('Attribute: ' . $attributeCode);
                    $this->logger->debug($e->getMessage());
                    $this->logger->debug('-------------------------');
                }
            }
        }

        $product->setData(AttributeProviderInterface::SKIP_TRANSLATION, true);
        try {
            $this->productResource->saveAttribute($product, AttributeProviderInterface::SKIP_TRANSLATION);
        } catch (Exception $e) {
            $this->logger->debug('Error when flagging product as "already translated"');
            $this->logger->debug('Product sku: ' . $product->getSku());
            $this->logger->debug('Store: ' . $storeName . '(id ' . $storeId . ')');
            $this->logger->debug($e->getMessage());
            $this->logger->debug('-------------------------');
        }

        $product->setData(AttributeProviderInterface::LAST_TRANSLATION, date('Y-m-d H:i:s'));
        try {
            $this->productResource->saveAttribute($product, AttributeProviderInterface::LAST_TRANSLATION);
        } catch (Exception $e) {
            $this->logger->debug('Error when saving translation date and time');
            $this->logger->debug('Product sku: ' . $product->getSku());
            $this->logger->debug('Store: ' . $storeName . '(id ' . $storeId . ')');
            $this->logger->debug($e->getMessage());
            $this->logger->debug('-------------------------');
        }
    }
}
