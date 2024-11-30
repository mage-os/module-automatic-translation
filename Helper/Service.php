<?php

namespace MageOS\AutomaticTranslation\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Service
 * @package MageOS\AutomaticTranslation\Helper
 */
class Service extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * Service constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ModuleConfig $moduleConfig
    )
    {
        $this->storeManager = $storeManager;
        $this->moduleConfig = $moduleConfig;

        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getStoresToTranslate(): array
    {
        $stores = $this->storeManager->getStores();
        $storeToTranslate = [];

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $storeName = $store->getName();

            if ($this->moduleConfig->isEnable($storeId)) {
                $storeToTranslate[$storeId] = $storeName;
            }
        }

        return $storeToTranslate;
    }

    /**
     * @return array
     */
    public function getStoresLanguages(): array
    {
        $stores = $this->storeManager->getStores();
        $storeToTranslate = [];

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $storeLanguage = $this->moduleConfig->getDestinationLanguage($storeId);

            if ($this->moduleConfig->isEnable($storeId)) {
                $storeToTranslate[$storeId] = $storeLanguage;
            }
        }
        return array_unique($storeToTranslate);
    }

    /**
     * @param $string
     * @return mixed
     */
    public function encodePageBuilderHtmlBox($string)
    {
        $dom = new \DOMDocument();

        preg_match('/<script.*?\/script>/s', $string, $scripts);
        $string = preg_replace('/<script.*?\/script>/s', '', $string);
        preg_match('/<style.*?\/style>/s', $string, $styles);
        $string = preg_replace('/<style.*?\/style>/s', '', $string);
        $dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);
        /**
         * @var \DOMNodeList $pagebuilderNodes
         */
        $pagebuilderNodes = $xpath->query('//div[@data-content-type="html"]');
        /**
         * @var \DOMNode $node
         */
        foreach ($pagebuilderNodes as $node) {
            /**
             * @var \DOMNode $childNode
             */
            $length = $node->childNodes->length;
            for ($nodeIndex = 0; $nodeIndex < $length; $nodeIndex++) {
                $childNode = $node->childNodes->item($nodeIndex);
                $childDom = new \DOMDocument();
                $importedNode = $childDom->importNode($childNode, true);
                $childDom->appendChild($importedNode);
                $nodeHtml = html_entity_decode($childDom->saveHTML());
                $node->replaceChild($dom->createTextNode(str_replace('>', '&gt;', str_replace('<', '&lt;', $nodeHtml))), $childNode);
            }
        }

        $resultNode = $dom->firstChild->childNodes->item(0);
        $resultHtml = preg_replace_callback(
            '/=\"(%7B%7B[^"]*%7D%7D)\"/m',
            function ($matches) {
                return urldecode($matches[0]);
            },
            $resultNode->nodeValue
        );
        foreach ($styles as $style) {
            $resultHtml = str_replace('>', '&gt;', str_replace('<', '&lt;', $style)) . $resultHtml;
        }
        foreach ($scripts as $script) {
            $resultHtml = $resultHtml . str_replace('>', '&gt;', str_replace('<', '&lt;', $script));
        }
        return '<div data-content-type="html" data-appearance="default" data-element="main">' . $resultHtml . '</div>';
    }

    /**
     * @param $string
     * @return mixed
     */
    public function parsePageBuilderHtmlBox($string)
    {
        if ($string !== strip_tags($string)) {
            try {
                $htmlString = htmlspecialchars_decode($string);
                $htmlString = preg_replace('/<script.*?\/script>/s', '', $htmlString);
                $htmlString = preg_replace('/<style.*?\/style>/s', '', $htmlString);
                $dom = new \DOMDocument();
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlString);
                $htmlContents = [];

                $xpath = new \DOMXPath($dom);
                if (
                    $xpath->query('//div[@data-content-type="html"]')->length > 0
                    && $xpath->query('//div[@data-content-type="html"]') !== false
                ) {
                    /**
                     * @var \DOMNode $node
                     */
                    foreach ($xpath->query('//div[@data-content-type="html"]') as $node) {
                        $this->getChildNodesContent($node, $htmlContents);
                    }
                    usort(
                        $htmlContents,
                        function ($str1, $str2) {
                            if (strlen($str1) == strlen($str2)) {
                                return 0;
                            }
                            return (strlen($str1) > strlen($str2)) ? -1 : 1;
                        }
                    );
                    $result = [];
                    foreach ($htmlContents as $content) {
                        $result[] = ["source" => $content];
                    }
                    return $result;
                }
            } catch (\Exception $e) {
                return $string;
            }
        }
        return $string;
    }

    /**
     * @param $str1
     * @param $str2
     * @return int
     */
    private function compareStringLength($str1, $str2)
    {
        if (strlen($str1) == strlen($str2)) {
            return 0;
        }
        return (strlen($str1) > strlen($str2)) ? -1 : 1;
    }


    /**
     * @param $node
     * @param $htmlContents
     */
    private function getChildNodesContent($node, &$htmlContents)
    {
        if (!in_array($node->nodeName, ['img', '#comment'])) {
            if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $childNode) {
                    $this->getChildNodesContent($childNode, $htmlContents);
                }
            } else {
                $textContent = '';
                if ($node->nodeName === "#text") {
                    $textContent = trim($node->data);
                } else {
                    $textContent = trim($node->textContent);
                }
                if (!empty($textContent) && strlen($textContent) > 2) {
                    $htmlContents[] = $textContent;
                }
            }
        }
    }
}
