<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Block\Adminhtml\CmsBlock;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Block\Adminhtml\Page\Edit\GenericButton;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;
use Magento\Framework\Exception\LocalizedException;

class GenerateTranslationsButton extends GenericButton implements ButtonProviderInterface
{
    const CMSBLOCK_TRANSLATION_CONTROLLER_PATH = 'automatic_translation/cms_block/generate';

    /**
     * @param Context $context
     * @param BlockRepositoryInterface $blockRepository
     * @param PageRepositoryInterface $pageRepository
     * @param ModuleConfig $moduleConfig
     * @param UrlInterface $url
     * @param Service $service
     */
    public function __construct(
        Context $context,
        protected BlockRepositoryInterface $blockRepository,
        PageRepositoryInterface $pageRepository,
        protected ModuleConfig $moduleConfig,
        protected UrlInterface $url,
        protected Service $service
    ) {
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

        if (empty($this->service->getStoresLanguages())) {
            return [];
        }

        $params = [
            'id' => $this->getCmsBlockId()
        ];

        return [
            'label' => __('Generate translations'),
            'class' => 'action-secondary',
            'on_click' => 'window.mageosTranslationPopup("' . $this->url->getUrl(
                    self::CMSBLOCK_TRANSLATION_CONTROLLER_PATH,
                    $params
                ) . '")',
            'sort_order' => 10
        ];
    }

    /**
     * @return int|null
     * @throws LocalizedException
     */
    protected function getCmsBlockId(): ?int
    {
        return $this->blockRepository->getById(
            $this->context->getRequest()->getParam('block_id')
        )->getId();
    }
}
