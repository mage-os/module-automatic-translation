<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Plugin;

use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\Message\ManagerInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Model\Config\Source\TextAttributes;
use MageOS\AutomaticTranslation\Model\Translator;
use MageOS\AutomaticTranslation\Service\TranslateParsedContent;
use Psr\Log\LoggerInterface as Logger;
use Exception;

class AdminhtmlProductBeforeSavePlugin
{
    /**
     * @param ModuleConfig $moduleConfig
     * @param Service $serviceHelper
     * @param Translator $translator
     * @param Gallery $gallery
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     * @param TranslateParsedContent $translateParsedContent
     */
    public function __construct(
        protected ModuleConfig $moduleConfig,
        protected Service $serviceHelper,
        protected Translator $translator,
        protected Gallery $gallery,
        protected ManagerInterface $messageManager,
        protected Logger $logger,
        protected TranslateParsedContent $translateParsedContent
    ) {
    }

    /**
     * @param Save $subject
     * @return ?array
     */
    public function beforeExecute(Save $subject): ?array
    {
        try {
            $request = $subject->getRequest();

            if ($request->getParam('translate') !== "true") {
                return null;
            }

            $storeId = (int) $request->getParam('store', 0);
            $sourceLanguage = $this->moduleConfig->getSourceLanguage();
            $destinationLanguage = $this->moduleConfig->getDestinationLanguage($storeId);

            if ($sourceLanguage === $destinationLanguage) {
                return null;
            }

            $requestPostValue = $request->getPostValue();
            $txtAttributesToTranslate = $this->moduleConfig->getProductTxtAttributeToTranslate($storeId);

            foreach ($txtAttributesToTranslate as $attributeCode) {
                if ($attributeCode === TextAttributes::GALLERY_ALT_ATTRIBUTE_CODE
                    && isset($requestPostValue["product"]["media_gallery"]["images"])
                ) {
                    foreach ($requestPostValue["product"]["media_gallery"]["images"] as $index => $mediaImage) {
                        $requestPostValue["product"]["media_gallery"]["images"][$index]["label"] = $this->translator
                            ->translate((string)$mediaImage["label"], $destinationLanguage, $sourceLanguage);
                    }
                    continue;
                }

                if (empty($requestPostValue["product"][$attributeCode])
                    || !is_string($requestPostValue["product"][$attributeCode])
                ) {
                    continue;
                }

                $originalValue = $requestPostValue["product"][$attributeCode];
                $parsedContent = $this->serviceHelper->parsePageBuilderHtmlBox($originalValue);

                $requestPostValue["product"][$attributeCode] = $this->translateParsedContent->execute(
                    $parsedContent,
                    $originalValue,
                    $destinationLanguage
                );

                if ($attributeCode === 'url_key') {
                    $requestPostValue["product"][$attributeCode] = strtolower(
                        preg_replace('#[^0-9a-z]+#i', '-', $requestPostValue["product"][$attributeCode])
                    );
                }

                if ($originalValue !== $requestPostValue["product"][$attributeCode]) {
                    $requestPostValue['use_default'][$attributeCode] = '0';
                }
            }

            $request->setPostValue($requestPostValue);
        } catch (Exception $e) {
            $this->logger->debug(__("An error translating product attributes: %s", $e->getMessage()));
            $this->messageManager->addErrorMessage(
                __("An error occurred translating product attributes. Try again later. %1", $e->getMessage())
            );
        }
        return null;
    }
}
