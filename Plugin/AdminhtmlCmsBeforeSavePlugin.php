<?php

namespace MageOS\AutomaticTranslation\Plugin;

use Exception;
use MageOS\AutomaticTranslation\Model\Translator;
use Magento\Framework\Message\ManagerInterface;
use MageOS\AutomaticTranslation\Helper\Service;
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
     * AdminhtmlCmsBeforeSavePlugin constructor.
     * @param Service $serviceHelper
     * @param Translator $translator
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     */
    public function __construct(
        Service $serviceHelper,
        Translator $translator,
        ManagerInterface $messageManager,
        Logger $logger
    ) {
        $this->serviceHelper = $serviceHelper;
        $this->translator = $translator;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
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
            $attributesToTranslate = $request->getParam('translationFields');

            foreach ($attributesToTranslate as $attributeCode) {
                if (isset($requestPostValue[$attributeCode]) && is_string($requestPostValue[$attributeCode])) {
                    $parsedContent = $this->serviceHelper->parsePageBuilderHtmlBox($requestPostValue[$attributeCode]);

                    $requestPostValue[$attributeCode] = $this->translateParsedContent($parsedContent, $requestPostValue[$attributeCode], $destinationLanguage);

                    if ($attributeCode === 'url_key') {
                        $requestPostValue[$attributeCode] = strtolower(preg_replace('#[^0-9a-z]+#i', '-', $requestPostValue[$attributeCode]));
                    }
                }
            }

            $request->setPostValue($requestPostValue);

        } catch (Exception $e) {
            $this->logger->debug(__("An error translating category attributes: %s", $e->getMessage()));
            $this->messageManager->addErrorMessage(__("An error occurred translating cms contents. Try again later."));
        }
        return null;
    }

    /**
     * @param mixed $parsedContent
     * @param string $requestPostValue
     * @param string $destinationLanguage
     * @return mixed|string
     */
    protected function translateParsedContent($parsedContent, string $requestPostValue, string $destinationLanguage) {
        if (is_string($parsedContent)) {
            return $this->translator->translate(
                $parsedContent,
                $destinationLanguage
            );
        } else {
            $requestPostValue = html_entity_decode(htmlspecialchars_decode($requestPostValue));

            foreach ($parsedContent as $parsedString) {
                $parsedString["translation"] = $this->translator->translate(
                    $parsedString["source"],
                    $destinationLanguage
                );

                $requestPostValue = str_replace($parsedString["source"], $parsedString["translation"], $requestPostValue);
            }

            return $this->serviceHelper->encodePageBuilderHtmlBox($requestPostValue);
        }
    }
}
