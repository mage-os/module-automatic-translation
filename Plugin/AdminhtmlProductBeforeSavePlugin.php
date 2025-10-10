<?php

namespace MageOS\AutomaticTranslation\Plugin;

use Exception;
use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\Message\ManagerInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Model\Config\Source\TextAttributes;
use MageOS\AutomaticTranslation\Model\Translator;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class AdminhtmlProductBeforeSavePlugin
 * @package MageOS\AutomaticTranslation\Plugin
 */
class AdminhtmlProductBeforeSavePlugin
{
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * @var Service
     */
    protected Service $serviceHelper;

    /**
     * @var Translator
     */
    protected Translator $translator;

    /**
     * @var Gallery
     */
    protected Gallery $gallery;

    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @param ModuleConfig $moduleConfig
     * @param Service $serviceHelper
     * @param Translator $translator
     * @param Gallery $gallery
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        Service $serviceHelper,
        Translator $translator,
        Gallery $gallery,
        ManagerInterface $messageManager,
        Logger $logger
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->serviceHelper = $serviceHelper;
        $this->translator = $translator;
        $this->gallery = $gallery;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    /**
     * @param Save $subject
     * @return null
     */
    public function beforeExecute(Save $subject)
    {
        try {
            $request = $subject->getRequest();

            if ($request->getParam('translate') !== "true") {
                return null;
            }

            $requestPostValue = $request->getPostValue();
            $storeId = $request->getParam('store', 0);
            $sourceLanguage = $this->moduleConfig->getSourceLanguage();
            $destinationLanguage = $this->moduleConfig->getDestinationLanguage($storeId);

            if ($sourceLanguage === $destinationLanguage) {
                return null;
            }

            $txtAttributesToTranslate = $this->moduleConfig->getProductTxtAttributeToTranslate($storeId);
            foreach ($txtAttributesToTranslate as $attributeCode) {
                if ($attributeCode === TextAttributes::GALLERY_ALT_ATTRIBUTE_CODE &&
                    isset($requestPostValue["product"]["media_gallery"]["images"])) {
                    $mediaGalleryImages = $requestPostValue["product"]["media_gallery"]["images"];
                    foreach ($mediaGalleryImages as $index => $mediaImage) {
                        $mediaGalleryImages[$index]["label"] = $this->translator
                            ->translate((string)$mediaImage["label"], $destinationLanguage, $sourceLanguage);
                    }
                    $requestPostValue["product"]["media_gallery"]["images"] = $mediaGalleryImages;
                } else {
                    if (empty($requestPostValue["product"][$attributeCode]) ||
                        !is_string($requestPostValue["product"][$attributeCode])
                    ) {
                        continue;
                    }

                    $originalValue = $requestPostValue["product"][$attributeCode];

                    $parsedContent = $this->serviceHelper
                        ->parsePageBuilderHtmlBox($requestPostValue["product"][$attributeCode]);

                    $requestPostValue["product"][$attributeCode] = $this->translateParsedContent(
                        $parsedContent,
                        $requestPostValue["product"][$attributeCode],
                        $destinationLanguage
                    );

                    if ($attributeCode === 'url_key') {
                        $requestPostValue["product"][$attributeCode] = strtolower(
                            preg_replace('#[^0-9a-z]+#i', '-', $requestPostValue["product"][$attributeCode])
                        );
                    }

                    $translatedValue = $requestPostValue["product"][$attributeCode];

                    if ($originalValue !== $translatedValue) {
                        $requestPostValue['use_default'][$attributeCode] = '0';
                    }
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

    /**
     * @param mixed $parsedContent
     * @param string $requestPostValue
     * @param string $destinationLanguage
     * @return mixed|string
     */
    protected function translateParsedContent($parsedContent, string $requestPostValue, string $destinationLanguage)
    {
        if (is_string($parsedContent)) {
            return $this->translator->translate(
                $parsedContent,
                $destinationLanguage
            );
        }

        $requestPostValue = html_entity_decode(
            htmlspecialchars_decode($requestPostValue)
        );
        foreach ($parsedContent as $parsedString) {
            $parsedString["translation"] = $this->translator->translate(
                (string)$parsedString["source"],
                $destinationLanguage
            );

            $requestPostValue = str_replace(
                $parsedString["source"],
                $parsedString["translation"],
                $requestPostValue
            );
        }
        return $this->serviceHelper->encodePageBuilderHtmlBox($requestPostValue);
    }
}
