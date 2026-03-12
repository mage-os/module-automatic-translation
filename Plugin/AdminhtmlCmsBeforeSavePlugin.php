<?php

namespace MageOS\AutomaticTranslation\Plugin;

use Exception;
use Magento\Framework\Message\ManagerInterface;
use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Service\TranslateParsedContent;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class AdminhtmlCmsBeforeSavePlugin
 * @package MageOS\AutomaticTranslation\Plugin
 */
class AdminhtmlCmsBeforeSavePlugin
{
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
     * AdminhtmlCmsBeforeSavePlugin constructor.
     * @param Service $serviceHelper
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     * @param TranslateParsedContent $translateParsedContent
     */
    public function __construct(
        Service $serviceHelper,
        ManagerInterface $messageManager,
        Logger $logger,
        TranslateParsedContent $translateParsedContent
    ) {
        $this->serviceHelper = $serviceHelper;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->translateParsedContent = $translateParsedContent;
    }

    /**
     * @param $subject
     * @return null
     */
    public function beforeExecute($subject)
    {
        try {
            $request = $subject->getRequest();

            if ($request->getParam('translate') !== "true") {
                return null;
            }

            $requestPostValue = $request->getPostValue();
            $destinationLanguage = $request->getParam('translationLanguage');
            $attributesToTranslate = $request->getParam('translationFields') ?? [];

            foreach ($attributesToTranslate as $attributeCode) {
                if (isset($requestPostValue[$attributeCode]) && is_string($requestPostValue[$attributeCode])) {
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
                }
            }

            $request->setPostValue($requestPostValue);
        } catch (Exception $e) {
            $this->logger->debug(sprintf('An error translating cms contents: %s', $e->getMessage()));
            $this->messageManager->addErrorMessage(__("An error occurred translating cms contents. Try again later."));
        }
        return null;
    }
}
