<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;

class MigrateConfigPaths implements DataPatchInterface
{
    const array ENCRYPTED_OLD_PATHS = [
        'automatic_translation/translations_engine/deepl_auth_key',
        'automatic_translation/translations_engine/openai_api_key',
        'automatic_translation/translations_engine/gemini_api_key',
    ];

    const array MAPPINGS = [
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

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected WriterInterface $configWriter,
        protected StoreManagerInterface $storeManager,
        protected EncryptorInterface $encryptor
    ) {
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        $scopes = [[ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0]];

        foreach ($this->storeManager->getWebsites() as $website) {
            $scopes[] = [ScopeInterface::SCOPE_WEBSITES, (int)$website->getId()];
        }

        foreach ($this->storeManager->getStores() as $store) {
            $scopes[] = [ScopeInterface::SCOPE_STORES, (int)$store->getId()];
        }

        foreach ($scopes as [$scope, $scopeId]) {
            foreach (self::MAPPINGS as $oldPath => $newPath) {
                $this->migratePath($oldPath, $newPath, $scope, $scopeId);
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

        if ($oldValue === null || $newValue !== null) {
            return;
        }

        if (in_array($oldPath, self::ENCRYPTED_OLD_PATHS, true)) {
            $oldValue = $this->encryptor->encrypt($oldValue);
        }

        $this->configWriter->save($newPath, $oldValue, $scope, $scopeId);
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
