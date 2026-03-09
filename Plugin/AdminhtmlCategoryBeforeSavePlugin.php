<?php

namespace MageOS\AutomaticTranslation\Plugin;

use Exception;
use Magento\Catalog\Controller\Adminhtml\Category\Save;
use Magento\Framework\Message\ManagerInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Service\TranslateParsedContent;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class AdminhtmlCategoryBeforeSavePlugin
 * @package MageOS\AutomaticTranslation\Plugin
 */
class AdminhtmlCategoryBeforeSavePlugin
{
    protected const CATEGORY_TRANSLATABLE_ATTRIBUTES = [
        'name',
        'description',
        'url_key',
        'meta_title',
        'meta_description',
        'meta_keywords'
    ];

    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * @var Service
     */
    protected Service $serviceHelper;

    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @var TranslateParsedContent
     */
    protected TranslateParsedContent $translateParsedContent;

    /**
     * AdminhtmlProductBeforeSavePlugin constructor.
     * @param ModuleConfig $moduleConfig
     * @param Service $serviceHelper
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     * @param TranslateParsedContent $translateParsedContent
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        Service $serviceHelper,
        ManagerInterface $messageManager,
        Logger $logger,
        TranslateParsedContent $translateParsedContent
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->serviceHelper = $serviceHelper;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->translateParsedContent = $translateParsedContent;
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

            $storeId = $request->getParam('store_id', 0);
            $sourceLanguage = $this->moduleConfig->getSourceLanguage();
            $destinationLanguage = $this->moduleConfig->getDestinationLanguage($storeId);

            if ($sourceLanguage === $destinationLanguage) {
                return null;
            }

            $requestPostValue = $request->getPostValue();
            $attributesToTranslate = self::CATEGORY_TRANSLATABLE_ATTRIBUTES;

            foreach ($attributesToTranslate as $attributeCode) {
                if (!empty($requestPostValue[$attributeCode]) && is_string($requestPostValue[$attributeCode])) {
                    $parsedContent = $this->serviceHelper->parsePageBuilderHtmlBox($requestPostValue[$attributeCode]);

                    $requestPostValue[$attributeCode] = $this->translateParsedContent->execute(
                        $parsedContent,
                        $requestPostValue[$attributeCode],
                        $destinationLanguage
                    );

                    if ($attributeCode === 'url_key') {
                        $requestPostValue[$attributeCode] = strtolower(
                            preg_replace('#[^0-9a-z]+#i', '-', $requestPostValue[$attributeCode])
                        );
                    }

                    if (isset($requestPostValue["use_default"][$attributeCode])) {
                        $requestPostValue["use_default"][$attributeCode] = "0";
                    }
                }
            }

            $request->setPostValue($requestPostValue);
        } catch (Exception $e) {
            $this->logger->debug(__("An error translating category attributes: %s", $e->getMessage()));
            $this->messageManager->addErrorMessage(
                __("An error occurred translating category attributes. Try again later.")
            );
        }
        return null;
    }
}
