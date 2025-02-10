<?php

namespace MageOS\AutomaticTranslation\Plugin;

use Exception;
use Magento\Catalog\Controller\Adminhtml\Category\Save;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Model\Translator;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class AdminhtmlCategoryBeforeSavePlugin
 * @package MageOS\AutomaticTranslation\Plugin
 */
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
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * AdminhtmlProductBeforeSavePlugin constructor.
     * @param ModuleConfig $moduleConfig
     * @param Service $serviceHelper
     * @param Translator $translator
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        Service $serviceHelper,
        Translator $translator,
        ManagerInterface $messageManager,
        Logger $logger
    )
    {
        $this->moduleConfig = $moduleConfig;
        $this->serviceHelper = $serviceHelper;
        $this->translator = $translator;
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
            $requestPostValue = $request->getPostValue();

            if ($request->getParam('translate') === "true") {
                $storeId = $request->getParam('store_id', 0);
                $sourceLanguage = $this->moduleConfig->getSourceLanguage();
                $destinationLanguage = $this->moduleConfig->getDestinationLanguage($storeId);

                if ($sourceLanguage !== $destinationLanguage) {
                    $attributesToTranslate = self::CATEGORY_TRANSLATABLE_ATTRIBUTES;

                    foreach ($attributesToTranslate as $attributeCode) {
                        if (!empty($requestPostValue[$attributeCode]) && is_string($requestPostValue[$attributeCode])) {
                            $parsedContent = $this->serviceHelper->parsePageBuilderHtmlBox($requestPostValue[$attributeCode]);

                            if (is_string($parsedContent)) {
                                $requestPostValue[$attributeCode] = $this->translator->translate(
                                    $parsedContent,
                                    $destinationLanguage
                                );
                            } else {
                                $requestPostValue[$attributeCode] = html_entity_decode(htmlspecialchars_decode($requestPostValue[$attributeCode]));
                                foreach ($parsedContent as $parsedString) {
                                    $parsedString["translation"] = $this->translator->translate(
                                        $parsedString["source"],
                                        $destinationLanguage
                                    );

                                    $requestPostValue[$attributeCode] = str_replace($parsedString["source"], $parsedString["translation"], $requestPostValue[$attributeCode]);
                                }

                                $requestPostValue[$attributeCode] = $this->serviceHelper->encodePageBuilderHtmlBox($requestPostValue[$attributeCode]);
                            }

                            if ($attributeCode === 'url_key') {
                                $requestPostValue[$attributeCode] = strtolower(preg_replace('#[^0-9a-z]+#i', '-',$requestPostValue[$attributeCode]));
                            }

                            if (isset($requestPostValue["use_default"][$attributeCode])) {
                                $requestPostValue["use_default"][$attributeCode] = "0";
                            }
                        }
                    }

                    $request->setPostValue($requestPostValue);
                }
            }
        } catch (Exception $e) {
            $this->logger->debug(__("An error translating category attributes: %s", $e->getMessage()));
            $this->messageManager->addErrorMessage(__("An error occurred translating category attributes. Try again later."));
        }
        return null;
    }
}
