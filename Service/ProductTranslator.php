<?php

namespace MageOS\AutomaticTranslation\Service;

use Magento\Framework\Exception\LocalizedException;
use MageOS\AutomaticTranslation\Api\ProductTranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service as ServiceHelper;
use MageOS\AutomaticTranslation\Api\AttributeProviderInterface;
use Magento\Framework\DataObject;
use Magento\Catalog\Api\Data\ProductInterface;
use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Psr\Log\LoggerInterface as Logger;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use MageOS\AutomaticTranslation\Model\Config\Source\TextAttributes;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogUrlRewrite\Model\Products\AppendUrlRewritesToProducts;
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
     * @var Gallery
     */
    protected Gallery $gallery;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var AppendUrlRewritesToProducts
     */
    protected AppendUrlRewritesToProducts $appendRewrites;
    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @param ModuleConfig $moduleConfig
     * @param ServiceHelper $serviceHelper
     * @param TranslatorInterface $translator
     * @param ProductResource $productResource
     * @param Gallery $gallery
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        ServiceHelper $serviceHelper,
        TranslatorInterface $translator,
        ProductResource $productResource,
        Gallery $gallery,
        StoreManagerInterface $storeManager,
        AppendUrlRewritesToProducts $appendRewrites,
        Logger $logger
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->serviceHelper = $serviceHelper;
        $this->translator = $translator;
        $this->productResource = $productResource;
        $this->gallery = $gallery;
        $this->storeManager = $storeManager;
        $this->appendRewrites = $appendRewrites;
        $this->logger = $logger;
    }

    /**
     * @param ProductInterface $product
     * @param string $targetLanguage
     * @param string $sourceLanguage
     * @param string $storeName
     * @param int $storeId
     * @throws LocalizedException
     */
    public function translateProduct(
        ProductInterface $product,
        string $targetLanguage,
        string $sourceLanguage,
        string $storeName = 'Default Store View',
        int $storeId = 0
    ): void {
        /** @var $product DataObject|ProductInterface */
        $attributesToTranslate = $this->moduleConfig->getProductTxtAttributeToTranslate($storeId);

        foreach ($attributesToTranslate as $attributeCode) {
            if ($attributeCode === TextAttributes::GALLERY_ALT_ATTRIBUTE_CODE) {
                $product->setStoreId($storeId);
                $gallery = $this->gallery->loadProductGalleryByAttributeId(
                    $product,
                    (int)$this->productResource
                        ->getAttribute(ProductInterface::MEDIA_GALLERY)->getAttributeId()
                );

                foreach ($gallery as $mediaImage) {
                    $altTextRows = $this->gallery->loadDataFromTableByValueId(
                        $this->gallery::GALLERY_VALUE_TABLE,
                        [$mediaImage["value_id"]]
                    );
                    $textToTranslate = null;
                    $translationPosition = 0;
                    $translationDisabled = 0;

                    foreach ($altTextRows as $altTextRow) {
                        if ($altTextRow["store_id"] === "0") {
                            $textToTranslate = $altTextRow["label"];
                        }
                        if ($altTextRow["store_id"] === (string)$storeId) {
                            $translationPosition = $altTextRow["position"];
                            $textToTranslate = $altTextRow["label"];
                            $translationDisabled = $altTextRow["disabled"];
                            $this->gallery->deleteGalleryValueInStore(
                                $mediaImage["value_id"],
                                $product->getId(),
                                $storeId
                            );
                            break;
                        }
                    }

                    if ($textToTranslate) {
                        $translatedText = $this->translator->translate(
                            $textToTranslate,
                            $targetLanguage,
                            $sourceLanguage
                        );
                        $this->gallery->insertGalleryValueInStore([
                            "value_id" => $mediaImage["value_id"],
                            "store_id" => $storeId,
                            "entity_id" => $product->getId(),
                            "label" => $translatedText,
                            "position" => $translationPosition,
                            "disabled" => $translationDisabled
                        ]);
                    }
                }
            } else {
                $textToTranslate = $product->getData($attributeCode);
                if (!empty($textToTranslate)) {
                    try {
                        $parsedContent = $this->serviceHelper->parsePageBuilderHtmlBox($textToTranslate);

                        if (is_string($parsedContent)) {
                            $textTranslated = $this->translator->translate(
                                $textToTranslate,
                                $targetLanguage,
                                $sourceLanguage
                            );
                        } else {
                            $textToTranslate = html_entity_decode(htmlspecialchars_decode($textToTranslate));
                            $textTranslated = $textToTranslate;

                            foreach ($parsedContent as $parsedString) {
                                $parsedString["translation"] = $this->translator->translate(
                                    $parsedString["source"],
                                    $targetLanguage
                                );

                                $textTranslated = str_replace(
                                    $parsedString["source"],
                                    $parsedString["translation"],
                                    $textTranslated
                                );
                            }

                            $textTranslated = $this->serviceHelper->encodePageBuilderHtmlBox($textTranslated);
                        }

                        if ($textToTranslate != $textTranslated) {
                            $product->setData($attributeCode, $textTranslated);
                            $this->productResource->saveAttribute($product, $attributeCode);

                            if ($this->moduleConfig->enableUrlRewrite($storeId) && $attributeCode === 'url_key') {
                                $storesToRewrite = [$this->storeManager->getStore($storeId)];
                                $this->appendRewrites->execute([$product], $storesToRewrite);
                            }
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
