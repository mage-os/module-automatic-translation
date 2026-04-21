<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Helper;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class Service extends AbstractHelper
{
    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Context $context,
        protected StoreManagerInterface $storeManager,
        protected ModuleConfig $moduleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getStoresToTranslate(): array
    {
        return $this->getEnabledStores(fn($store) => $store->getName());
    }

    /**
     * @return array
     */
    public function getStoresLanguages(): array
    {
        return array_unique(
            $this->getEnabledStores(fn($store) => $this->moduleConfig->getDestinationLanguage((int)$store->getId()))
        );
    }

    /**
     * @param callable $valueExtractor
     * @return array
     */
    protected function getEnabledStores(callable $valueExtractor): array
    {
        $result = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->moduleConfig->isEnable((int)$store->getId())) {
                $result[$store->getId()] = $valueExtractor($store);
            }
        }
        return $result;
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
        $pagebuilderNodes = $xpath->query('//*[@data-content-type="html"]');

        if ($pagebuilderNodes === false) {
            return '<div data-content-type="html" data-appearance="default" data-element="main">' . $string . '</div>';
        }

        foreach ($pagebuilderNodes as $node) {
            $length = $node->childNodes->length;
            for ($nodeIndex = 0; $nodeIndex < $length; $nodeIndex++) {
                $childNode = $node->childNodes->item($nodeIndex);
                if ($childNode === null) {
                    continue;
                }
                $childDom = new DOMDocument();
                $importedNode = $childDom->importNode($childNode, true);
                $childDom->appendChild($importedNode);
                $nodeHtml = html_entity_decode((string)$childDom->saveHTML());
                $node->replaceChild(
                    $dom->createTextNode(str_replace(['<', '>'], ['&lt;', '&gt;'], $nodeHtml)),
                    $childNode
                );
            }
        }

        $resultHtml = '';
        $root = $dom->firstChild;

        if ($root !== null) {
            foreach ($root->childNodes as $node) {
                $resultHtml .= (string)$dom->saveHTML($node);
            }
        }

        $resultHtml = preg_replace_callback(
            '/(<div[^>]*data-content-type="html"[^>]*>)(.*?)(<\/div>)/s',
            fn($matches) => $matches[1] . html_entity_decode($matches[2], ENT_NOQUOTES, 'UTF-8') . $matches[3],
            $resultHtml
        ) ?? $resultHtml;

        $resultHtml = preg_replace_callback(
            '/="(%7B%7B[^"]*%7D%7D)"/m',
            fn($matches) => urldecode($matches[0]),
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
    public function parsePageBuilderHtmlBox(string $string): array|string
    {
        if ($string !== strip_tags($string)) {
            try {
                $htmlString = htmlspecialchars_decode($this->stripContentSettings($string));
                $htmlString = preg_replace('/<script.*?\/script>/s', '', $htmlString) ?? '';
                $htmlString = preg_replace('/<style.*?\/style>/s', '', $htmlString) ?? '';
                $dom = new DOMDocument();
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlString);

                $xpath = new DOMXPath($dom);
                $queryResult = $xpath->query(
                    '//*[@data-content-type="html" or @data-content-type="text" or @data-content-type="heading"]'
                );

                if ($queryResult !== false && $queryResult->length > 0) {
                    $htmlContents = [];
                    /** @var DOMNode $node */
                    foreach ($queryResult as $node) {
                        $this->getChildNodesContent($node, $htmlContents);
                    }
                    usort($htmlContents, fn(string $a, string $b) => strlen($b) <=> strlen($a));
                    return array_map(fn($content) => ['source' => $content], $htmlContents);
                }
            } catch (Exception) {
                return $string;
            }
        }
        return $string;
    }

    /**
     * @param DOMNode $node
     * @param array $htmlContents
     */
    protected function getChildNodesContent(DOMNode $node, array &$htmlContents): void
    {
        if (in_array($node->nodeName, ['img', '#comment'], true)) {
            return;
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                $this->getChildNodesContent($childNode, $htmlContents);
            }
            return;
        }

        $textContent = trim(
            $node->nodeName === '#text' ? ($node->nodeValue ?? '') : ($node->textContent ?? '')
        );

        if ($textContent !== '' && strlen($textContent) > 2) {
            $htmlContents[] = $textContent;
        }
    }
}
