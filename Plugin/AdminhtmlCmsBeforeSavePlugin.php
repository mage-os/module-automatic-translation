<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Plugin;

use Magento\Framework\Message\ManagerInterface;
use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Service\TranslateParsedContent;
use Psr\Log\LoggerInterface as Logger;
use Exception;

class AdminhtmlCmsBeforeSavePlugin
{
    /**
     * @param Service $serviceHelper
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     * @param TranslateParsedContent $translateParsedContent
     */
    public function __construct(
        protected Service $serviceHelper,
        protected ManagerInterface $messageManager,
        protected Logger $logger,
        protected TranslateParsedContent $translateParsedContent
    ) {
    }

    /**
     * @param mixed $subject
     * @return ?array
     */
    public function beforeExecute(mixed $subject): ?array
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

                    if ($attributeCode === 'identifier') {
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
