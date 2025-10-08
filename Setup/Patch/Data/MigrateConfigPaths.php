<?php
declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class MigrateConfigPaths
 * @package MageOS\AutomaticTranslation\Setup\Patch\Data
 */
class MigrateConfigPaths implements DataPatchInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;
    /**
     * @var WriterInterface
     */
    protected WriterInterface $configWriter;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->storeManager = $storeManager;
    }

    /**
     * @return void
     */
    public function apply()
    {
        $mappings = [
            'automatic_translation/general/enable' => 'ai_integration/automatic_translation/general/enable',
            'automatic_translation/general/source_language' => 'ai_integration/automatic_translation/general/source_language',
            'automatic_translation/general/destination_language' => 'general/locale/code',
            'automatic_translation/catalog/text_attribute_to_translate' => 'ai_integration/automatic_translation/catalog/text_attribute_to_translate',
            'automatic_translation/catalog/select_attribute_to_translate' => 'ai_integration/automatic_translation/catalog/select_attribute_to_translate',
            'automatic_translation/catalog/enable_periodic_retranslation' => 'ai_integration/automatic_translation/catalog/enable_periodic_retranslation',
            'automatic_translation/catalog/retranslation_period' => 'ai_integration/automatic_translation/catalog/retranslation_period',
            'automatic_translation/catalog/translate_disabled' => 'ai_integration/automatic_translation/catalog/translate_disabled',
            'automatic_translation/catalog/enable_url_rewrite' => 'ai_integration/automatic_translation/catalog/enable_url_rewrite',
            'automatic_translation/translations_engine/engine' => 'ai_integration/automatic_translation/translations_engine/engine',
            'automatic_translation/translations_engine/deepl_auth_key' => 'ai_integration/automatic_translation/translations_engine/deepl_auth_key',
            'automatic_translation/translations_engine/openai_org_id' => 'ai_integration/automatic_translation/translations_engine/openai_org_id',
            'automatic_translation/translations_engine/openai_api_key' => 'ai_integration/automatic_translation/translations_engine/openai_api_key',
            'automatic_translation/translations_engine/openai_project_id' => 'ai_integration/automatic_translation/translations_engine/openai_project_id',
            'automatic_translation/translations_engine/openai_model' => 'ai_integration/automatic_translation/translations_engine/openai_model',
            'automatic_translation/translations_engine/gemini_api_key' => 'ai_integration/automatic_translation/translations_engine/gemini_api_key',
            'automatic_translation/translations_engine/gemini_model' => 'ai_integration/automatic_translation/translations_engine/gemini_model',
        ];

        // Default scope
        foreach ($mappings as $oldPath => $newPath) {
            $this->migratePath($oldPath, $newPath, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        }

        // Websites
        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($mappings as $oldPath => $newPath) {
                $this->migratePath($oldPath, $newPath, ScopeInterface::SCOPE_WEBSITES, (int)$website->getId());
            }
        }

        // Stores
        foreach ($this->storeManager->getStores() as $store) {
            foreach ($mappings as $oldPath => $newPath) {
                $this->migratePath($oldPath, $newPath, ScopeInterface::SCOPE_STORES, (int)$store->getId());
            }
        }
    }

    /**
     * @param string $oldPath
     * @param string $newPath
     * @param string $scope
     * @param int $scopeId
     * @return void
     */
    protected function migratePath(string $oldPath, string $newPath, string $scope, int $scopeId): void
    {
        $oldValue = $this->scopeConfig->getValue($oldPath, $scope, $scopeId);
        $newValue = $this->scopeConfig->getValue($newPath, $scope, $scopeId);

        if ($oldValue !== null && $newValue === null) {
            $this->configWriter->save($newPath, $oldValue, $scope, $scopeId);
        }
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
