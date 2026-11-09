<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Delete the legacy automatic_translation config rows that MigrateConfigPaths left behind.
 *
 * MigrateConfigPaths copied the values to the new paths but kept the legacy rows, so the API keys
 * stayed readable in plain text in core_config_data. Before this patch deletes a legacy row, it copies
 * the row to the new path in the same scope when no value is stored there, so no store-specific value
 * gets lost.
 */
class DeleteLegacyConfigPaths implements DataPatchInterface
{
    /**
     * @param CollectionFactory $configCollectionFactory
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        protected CollectionFactory $configCollectionFactory,
        protected WriterInterface $configWriter,
        protected EncryptorInterface $encryptor
    ) {
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        $legacyRows = $this->configCollectionFactory->create()
            ->addFieldToFilter('path', ['in' => array_keys(MigrateConfigPaths::MAPPINGS)]);

        if ($legacyRows->getSize() === 0) {
            return;
        }

        $storedNewPaths = $this->getStoredNewPaths();

        foreach ($legacyRows as $row) {
            $oldPath = (string)$row->getPath();
            $newPath = MigrateConfigPaths::MAPPINGS[$oldPath];
            $scope = (string)$row->getScope();
            $scopeId = (int)$row->getScopeId();
            $value = $row->getValue();

            if ($value !== null && !isset($storedNewPaths[$this->getKey($newPath, $scope, $scopeId)])) {
                if (in_array($oldPath, MigrateConfigPaths::ENCRYPTED_OLD_PATHS, true) && !$this->isEncrypted($value)) {
                    $value = $this->encryptor->encrypt($value);
                }

                $this->configWriter->save($newPath, $value, $scope, $scopeId);
            }

            $this->configWriter->delete($oldPath, $scope, $scopeId);
        }
    }

    /**
     * Return the scopes in which each new path has a stored value, keyed by path, scope and scope ID.
     *
     * @return array<string, true>
     */
    protected function getStoredNewPaths(): array
    {
        $rows = $this->configCollectionFactory->create()
            ->addFieldToFilter('path', ['in' => array_values(MigrateConfigPaths::MAPPINGS)]);

        $stored = [];
        foreach ($rows as $row) {
            $stored[$this->getKey((string)$row->getPath(), (string)$row->getScope(), (int)$row->getScopeId())] = true;
        }

        return $stored;
    }

    /**
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    protected function getKey(string $path, string $scope, int $scopeId): string
    {
        return $scope . '|' . $scopeId . '|' . $path;
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
        return [
            MigrateConfigPaths::class,
        ];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
