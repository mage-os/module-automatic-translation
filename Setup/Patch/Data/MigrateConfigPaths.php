<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;

class MigrateConfigPaths implements DataPatchInterface
{
    const ENCRYPTED_OLD_PATHS = [
        'automatic_translation/translations_engine/deepl_auth_key',
        'automatic_translation/translations_engine/openai_api_key',
        'automatic_translation/translations_engine/gemini_api_key',
    ];

    const MAPPINGS = [
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
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected WriterInterface $configWriter,
        protected StoreManagerInterface $storeManager,
        protected EncryptorInterface $encryptor,
        protected ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Move every stored legacy row to its new path in the same scope, then delete the legacy row.
     *
     * Only rows that exist in core_config_data are processed, so inherited values are not copied
     * into lower scopes. The legacy row is always removed, which keeps no plaintext API key behind
     * and makes the migration safe to run again.
     *
     * @return void
     */
    public function apply(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('core_config_data');

        $legacyRows = $connection->fetchAll(
            $connection->select()
                ->from($table, ['scope', 'scope_id', 'path', 'value'])
                ->where('path IN (?)', array_keys(self::MAPPINGS))
        );

        foreach ($legacyRows as $row) {
            $this->migrateRow(
                $row['path'],
                self::MAPPINGS[$row['path']],
                $row['value'],
                (string)$row['scope'],
                (int)$row['scope_id']
            );
        }
    }

    /**
     * @param string $oldPath
     * @param string $newPath
     * @param string|null $oldValue
     * @param string $scope
     * @param int $scopeId
     * @return void
     */
    protected function migrateRow(
        string $oldPath,
        string $newPath,
        ?string $oldValue,
        string $scope,
        int $scopeId
    ): void {
        if ($oldValue !== null && !$this->hasStoredValue($newPath, $scope, $scopeId)) {
            if (in_array($oldPath, self::ENCRYPTED_OLD_PATHS, true) && !$this->isEncrypted($oldValue)) {
                $oldValue = $this->encryptor->encrypt($oldValue);
            }

            $this->configWriter->save($newPath, $oldValue, $scope, $scopeId);
        }

        $this->configWriter->delete($oldPath, $scope, $scopeId);
    }

    /**
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    protected function hasStoredValue(string $path, string $scope, int $scopeId): bool
    {
        $connection = $this->resourceConnection->getConnection();

        return (bool)$connection->fetchOne(
            $connection->select()
                ->from($this->resourceConnection->getTableName('core_config_data'), ['config_id'])
                ->where('path = ?', $path)
                ->where('scope = ?', $scope)
                ->where('scope_id = ?', $scopeId)
        );
    }

    /**
     * Detect a value that Magento already encrypted, so it is not encrypted a second time.
     *
     * @param string $value
     * @return bool
     */
    protected function isEncrypted(string $value): bool
    {
        if (preg_match('/^\d+:\d+:\S+$/', $value) !== 1) {
            return false;
        }

        try {
            return $this->encryptor->decrypt($value) !== '';
        } catch (\Throwable) {
            return false;
        }
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
