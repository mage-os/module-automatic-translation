<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Plugin;

use Magento\Catalog\Controller\Adminhtml\Category\Save;
use Magento\Framework\Message\ManagerInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Service\TranslateParsedContent;
use Psr\Log\LoggerInterface as Logger;
use Exception;

class AdminhtmlCategoryBeforeSavePlugin
{
    const CATEGORY_TRANSLATABLE_ATTRIBUTES = [
        'name',
        'description',
        'url_key',
        'meta_title',
        'meta_description',
        'meta_keywords'
    ];

    /**
     * @param ModuleConfig $moduleConfig
     * @param Service $serviceHelper
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     * @param TranslateParsedContent $translateParsedContent
     */
    public function __construct(
        protected ModuleConfig $moduleConfig,
        protected Service $serviceHelper,
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

            $storeId = (int) $request->getParam('store_id', 0);
            $sourceLanguage = $this->moduleConfig->getSourceLanguage();
            $destinationLanguage = $this->moduleConfig->getDestinationLanguage($storeId);

            if ($sourceLanguage === $destinationLanguage) {
                return null;
            }

            $requestPostValue = $request->getPostValue();

            foreach (self::CATEGORY_TRANSLATABLE_ATTRIBUTES as $attributeCode) {
                if (!empty($requestPostValue[$attributeCode]) && is_string($requestPostValue[$attributeCode])) {
                    $parsedContent = $this->serviceHelper->parsePageBuilderHtmlBox($requestPostValue[$attributeCode]);

                    $requestPostValue[$attributeCode] = $this->translateParsedContent->execute(
                        $parsedContent,
                        $requestPostValue[$attributeCode],
                        $destinationLanguage
                    );

                    if ($attributeCode === 'url_key') {
                        $requestPostValue[$attributeCode] = strtolower(
                            (string)preg_replace('#[^0-9a-z]+#i', '-', $requestPostValue[$attributeCode])
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
