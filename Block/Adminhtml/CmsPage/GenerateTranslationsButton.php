<?php

namespace MageOS\AutomaticTranslation\Block\Adminhtml\CmsPage;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Block\Adminhtml\Page\Edit\GenericButton;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;

/**
 * Class GenerateTranslationsButton
 * @package MageOS\AutomaticTranslation\Block\Adminhtml\CmsPage
 */
class GenerateTranslationsButton extends GenericButton implements ButtonProviderInterface
{
    protected const CMSPAGE_TRANSLATION_CONTROLLER_PATH = 'automatic_translation/cms_page/generate';

    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $url;

    /**
     * @var Service
     */
    protected Service $service;

    /**
     * @param Context $context
     * @param PageRepositoryInterface $pageRepository
     * @param ModuleConfig $moduleConfig
     * @param UrlInterface $url
     * @param Service $service
     */
    public function __construct(
        Context $context,
        PageRepositoryInterface $pageRepository,
        ModuleConfig $moduleConfig,
        UrlInterface $url,
        Service $service
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->url = $url;
        $this->service = $service;
        parent::__construct($context, $pageRepository);
    }

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        if (!$this->moduleConfig->isEnable()) {
            return [];
        }

        if (empty($this->service->getStoresLanguages())) {
            return [];
        }

        $params = [
            'id' => $this->getPageId()
        ];

        return [
            'label' => __('Generate translations'),
            'class' => 'action-secondary',
            'on_click' => 'window.mageosTranslationPopup("' . $this->url->getUrl(
                    self::CMSPAGE_TRANSLATION_CONTROLLER_PATH,
                    $params
                ) . '")',
            'sort_order' => 10
        ];
    }
}
