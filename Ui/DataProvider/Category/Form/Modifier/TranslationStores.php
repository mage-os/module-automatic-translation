<?php

namespace MageOS\AutomaticTranslation\Ui\DataProvider\Category\Form\Modifier;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ResourceModel\Store;
use Magento\Store\Model\Website;
use Magento\Ui\Component;
use Magento\Ui\Component\Container;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Backend\Block\Store\Switcher as StoreSwitcher;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Magento\Catalog\Api\CategoryRepositoryInterface;

/**
 * Class TranslationStores
 * @package MageOS\AutomaticTranslation\Ui\DataProvider\Category\Form\Modifier
 */
class TranslationStores extends AbstractModifier
{
    const GROUP_CODE = 'translation-stores';

    /**
     * @var StoreSwitcher
     */
    private StoreSwitcher $storeSwitcher;

    /**
     * @var ModuleConfig
     */
    private ModuleConfig $moduleConfig;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * TranslationStores constructor.
     * @param StoreSwitcher $storeSwitcher
     * @param ModuleConfig $moduleConfig
     * @param RequestInterface $request
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        StoreSwitcher $storeSwitcher,
        ModuleConfig $moduleConfig,
        RequestInterface $request,
        CategoryRepositoryInterface $categoryRepository
    )
    {
        $this->storeSwitcher = $storeSwitcher;
        $this->moduleConfig = $moduleConfig;
        $this->request = $request;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @inheritdoc
     *
     * @since 101.0.0
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     *
     * @since 101.0.0
     */
    public function modifyMeta(array $meta)
    {
        if (isset($meta[static::GROUP_CODE])) {
            $meta[static::GROUP_CODE]['arguments']['data']['config']['component'] =
                'Magento_Ui/js/form/components/fieldset';
        }

        $meta = $this->customizeSwitchStoreModal($meta);
        $meta = $this->customizeTranslationStoresList($meta);

        return $meta;
    }

    /**
     * Modify meta customize switch store modal.
     *
     * @param array $meta
     * @return array
     */
    private function customizeSwitchStoreModal(array $meta)
    {
        $meta['select_store_modal']['arguments']['data']['config'] = [
            'isTemplate' => false,
            'componentType' => Component\Modal::NAME,
            'dataScope' => '',
            'provider' => 'category_form.category_form_data_source',
            'options' => [
                'title' => __('Choose store-view to translate'),
                'buttons' => [
                    [
                        'text' => 'Cancel',
                        'actions' => [
                            [
                                'targetName' => '${ $.name }',
                                '__disableTmpl' => ['targetName' => false],
                                'actionName' => 'actionCancel'
                            ]
                        ]
                    ]
                ],
            ],
        ];
        return $meta;
    }

    /**
     * @param array $meta
     * @return array
     * @throws NoSuchEntityException
     */
    private function customizeTranslationStoresList(array $meta)
    {
        $meta['select_store_modal']['children']['translation_store_list'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'component' => 'MageOS_AutomaticTranslation/js/components/translation-stores-listing',
                        'componentType' => Container::NAME,
                        'translationSwitcherMessage' => __('In order to translate contents you must switch to store-view scope. Please choose one of the following store-views.'),
                        'noStoresMessage' => __('Seems that your category isn\'t associated to any translatable store-view. Please check store-view scopes configurations at "Stores > Configuration > MageOS > Automatic translation with AI".'),
                        'content' => '',
                        'storeSwitchUrl' => $this->storeSwitcher->getSwitchUrl(),
                        'translationStores' => $this->getTranslationStores()
                    ],
                ],
            ],
        ];
        return $meta;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    private function getTranslationStores() {
        $translationStores = [];
        try {
            $currentCategory = $this->categoryRepository->get(
                $this->request->getParam("id")
            );
            $categoryStoreIds = $currentCategory->getStoreIds();

            /**
             * @var Website $website
             */
            foreach ($this->storeSwitcher->getWebsites() as $website) {
                $stores = $website->getStores();
                /**
                 * @var  Store $store
                 */
                foreach ($stores as $store) {
                    if (in_array($store->getId(), $categoryStoreIds)) {
                        if ($this->moduleConfig->isEnable($store->getId())) {
                            $translationStores[$store->getId()] = $store->getName();
                        }
                    }
                }
            }
        } catch (\Exception $e) {}
        return $translationStores;
    }
}
