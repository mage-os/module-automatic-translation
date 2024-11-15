<?php

namespace MageOS\AutomaticTranslation\Block\Adminhtml\CmsBlock;

use Magento\Backend\Block\Widget\Context;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Block\Adminhtml\Page\Edit\GenericButton;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Magento\Backend\Model\UrlInterface;

/**
 * Class GenerateTranslationsButton
 * @package MageOS\AutomaticTranslation\Block\Adminhtml\CmsBlock
 */
class GenerateTranslationsButton extends GenericButton implements ButtonProviderInterface
{
    const CMSBLOCK_TRANSLATION_CONTROLLER_PATH = 'automatic_translation/cms_block/generate';

    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $url;

    /**
     * @var BlockRepositoryInterface
     */
    protected BlockRepositoryInterface $blockRepository;

    /**
     * GenerateTranslationsButton constructor.
     * @param Context $context
     * @param BlockRepositoryInterface $blockRepository
     * @param PageRepositoryInterface $pageRepository
     * @param ModuleConfig $moduleConfig
     * @param UrlInterface $url
     */
    public function __construct(
        Context $context,
        BlockRepositoryInterface $blockRepository,
        PageRepositoryInterface $pageRepository,
        ModuleConfig $moduleConfig,
        UrlInterface $url
    )
    {
        $this->moduleConfig = $moduleConfig;
        $this->url = $url;
        $this->blockRepository = $blockRepository;
        parent::__construct($context, $pageRepository);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getButtonData(): array
    {
        if (!$this->moduleConfig->isEnable() || !$this->context->getRequest()->getParam('block_id')) {
            return [];
        }

        $params = [
            'id' => $this->getCmsBlockId()
        ];

        return [
            'label' => __('Generate translations'),
            'class' => 'action-secondary',
            'on_click' => 'window.mageosTranslationPopup("' . $this->url->getUrl(self::CMSBLOCK_TRANSLATION_CONTROLLER_PATH, $params) . '")',
            'sort_order' => 10
        ];
    }

    /**
     * @return int|null
     * @throws LocalizedException
     */
    private function getCmsBlockId() {
        return $this->blockRepository->getById(
            $this->context->getRequest()->getParam('block_id')
        )->getId();
    }
}
