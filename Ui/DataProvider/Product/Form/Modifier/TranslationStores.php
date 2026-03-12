<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Ui\DataProvider\Product\Form\Modifier;

use Magento\Backend\Block\Store\Switcher as StoreSwitcher;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ResourceModel\Store;
use Magento\Store\Model\Website;
use Magento\Ui\Component;
use Magento\Ui\Component\Container;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Magento\Framework\Exception\NoSuchEntityException;

class TranslationStores extends AbstractModifier
{
    const string GROUP_CODE = 'translation-stores';

    /**
     * @param StoreSwitcher $storeSwitcher
     * @param ModuleConfig $moduleConfig
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        protected StoreSwitcher $storeSwitcher,
        protected ModuleConfig $moduleConfig,
        protected RequestInterface $request,
        protected ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     * @throws NoSuchEntityException
     */
    public function modifyMeta(array $meta): array
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
     * @param array $meta
     * @return array
     */
    protected function customizeSwitchStoreModal(array $meta): array
    {
        $meta['select_store_modal']['arguments']['data']['config'] = [
            'isTemplate' => false,
            'componentType' => Component\Modal::NAME,
            'dataScope' => '',
            'provider' => 'product_form.product_form_data_source',
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
    protected function customizeTranslationStoresList(array $meta): array
    {
        $meta['select_store_modal']['children']['translation_store_list'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'component' => 'MageOS_AutomaticTranslation/js/components/translation-stores-listing',
                        'componentType' => Container::NAME,
                        'translationSwitcherMessage' => __(
                            'In order to translate contents you must switch to store-view scope. Please choose one of the following store-views.'
                        ),
                        'noStoresMessage' => __(
                            'Seems that your product isn\'t associated to any translatable store-view. Please check store-view scopes configurations at "Stores > Configuration > MageOS > Automatic translation with AI".'
                        ),
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
    protected function getTranslationStores(): array
    {
        $translationStores = [];
        try {
            $currentProduct = $this->productRepository->getById(
                $this->request->getParam("id")
            );
            $productStoreIds = $currentProduct->getStoreIds();

            /** @var Website $website */
            foreach ($this->storeSwitcher->getWebsites() as $website) {
                $stores = $website->getStores();
                /** @var Store $store */
                foreach ($stores as $store) {
                    if (in_array($store->getId(), $productStoreIds)) {
                        if ($this->moduleConfig->isEnable((int)$store->getId())) {
                            $translationStores[(int)$store->getId()] = $store->getName();
                        }
                    }
                }
            }
        } catch (NoSuchEntityException) {
            return $translationStores;
        }
        return $translationStores;
    }
}
