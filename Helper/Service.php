<?php
declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Helper;

use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Exception;
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
    ) {
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

            if ($this->moduleConfig->isEnable((int)$storeId)) {
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
            $storeLanguage = $this->moduleConfig->getDestinationLanguage((int)$storeId);

            if ($this->moduleConfig->isEnable((int)$storeId)) {
                $storeToTranslate[$storeId] = $storeLanguage;
            }
        }
        return array_unique($storeToTranslate);
    }

    /**
     * @param string $html
     * @return string
     */
    protected function stripContentSettings(string $html): string
    {
        return preg_replace('/\s*content_settings="[^"]*"/', '', $html) ?? $html;
    }

    /**
     * @param string $string
     * @return string
     */
    public function encodePageBuilderHtmlBox(string $string): string
    {
        $dom = new DOMDocument();

        preg_match('/<script.*?\/script>/s', $string, $scripts);
        $string = preg_replace('/<script.*?\/script>/s', '', $string) ?? '';
        preg_match('/<style.*?\/style>/s', $string, $styles);
        $string = preg_replace('/<style.*?\/style>/s', '', $string) ?? '';
        $dom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $string,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new DOMXPath($dom);
        /**
         * @var DOMNodeList $pagebuilderNodes
         */
        $pagebuilderNodes = $xpath->query(
            '//*[@data-content-type="html"]'
        );

        if ($pagebuilderNodes === false) {
            return '<div data-content-type="html" data-appearance="default" data-element="main">' . $string . '</div>';
        }

        /**
         * @var DOMNode $node
         */
        foreach ($pagebuilderNodes as $node) {
            /**
             * @var DOMNode $childNode
             */
            $length = $node->childNodes->length;
            for ($nodeIndex = 0; $nodeIndex < $length; $nodeIndex++) {
                $childNode = $node->childNodes->item($nodeIndex);
                if ($childNode === null) {
                    continue;
                }
                $childDom = new DOMDocument();
                $importedNode = $childDom->importNode($childNode, true);
                $childDom->appendChild($importedNode);
                $nodeHtml = html_entity_decode($childDom->saveHTML());
                $node->replaceChild($dom->createTextNode(str_replace(['<', '>'], ['&lt;', '&gt;'], (string)$nodeHtml)),
                    $childNode);
            }
        }

        $resultHtml = '';
        $root = $dom->firstChild;

        if ($root !== null) {
            foreach ($root->childNodes as $node) {
                $resultHtml .= $dom->saveHTML($node);
            }
        }

        $resultHtml = preg_replace_callback(
            '/(<div[^>]*data-content-type="html"[^>]*>)(.*?)(<\/div>)/s',
            function ($matches) {
                return $matches[1] . html_entity_decode($matches[2], ENT_NOQUOTES, 'UTF-8') . $matches[3];
            },
            $resultHtml
        ) ?? $resultHtml;

        $resultHtml = preg_replace_callback(
            '/="(%7B%7B[^"]*%7D%7D)"/m',
            function ($matches) {
                return urldecode($matches[0]);
            },
            $resultHtml
        ) ?? '';

        foreach ($styles as $style) {
            $resultHtml = (string)$style . $resultHtml;
        }
        foreach ($scripts as $script) {
            $resultHtml = $resultHtml . (string)$script;
        }

        return $resultHtml;
    }

    /**
     * @param string $string
     * @return array|string
     */
    public function parsePageBuilderHtmlBox(string $string)
    {
        if ($string !== strip_tags($string)) {
            try {
                $htmlString = htmlspecialchars_decode($this->stripContentSettings($string));
                $htmlString = preg_replace('/<script.*?\/script>/s', '', $htmlString) ?? '';
                $htmlString = preg_replace('/<style.*?\/style>/s', '', $htmlString) ?? '';
                $dom = new DOMDocument();
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlString);
                $htmlContents = [];

                $xpath = new DOMXPath($dom);
                $queryResult = $xpath->query(
                    '//*[@data-content-type="html" or @data-content-type="text" or @data-content-type="heading"]'
                );

                if (
                    $queryResult !== false
                    && $queryResult->length > 0
                ) {
                    /**
                     * @var DOMNode $node
                     */
                    foreach ($queryResult as $node) {
                        $this->getChildNodesContent($node, $htmlContents);
                    }
                    usort(
                        $htmlContents,
                        [$this, 'compareStringLength']
                    );
                    $result = [];
                    foreach ($htmlContents as $content) {
                        $result[] = ["source" => $content];
                    }
                    return $result;
                }
            } catch (Exception $e) {
                return $string;
            }
        }
        return $string;
    }


    /**
     * @param string $str1
     * @param string $str2
     * @return int
     */
    private function compareStringLength(string $str1, string $str2): int
    {
        if (strlen($str1) === strlen($str2)) {
            return 0;
        }
        return (strlen($str1) > strlen($str2)) ? -1 : 1;
    }


    /**
     * @param DOMNode $node
     * @param array $htmlContents
     */
    private function getChildNodesContent(DOMNode $node, array &$htmlContents): void
    {
        if (!in_array($node->nodeName, ['img', '#comment'], true)) {
            if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $childNode) {
                    $this->getChildNodesContent($childNode, $htmlContents);
                }
            } else {
                $textContent = '';
                if ($node->nodeName === "#text") {
                    $textContent = trim($node->nodeValue ?? '');
                } else {
                    $textContent = trim($node->textContent ?? '');
                }
                if (!empty($textContent) && strlen($textContent) > 2) {
                    $htmlContents[] = $textContent;
                }
            }
        }
    }
}
