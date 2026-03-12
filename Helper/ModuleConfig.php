<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Helper;

use DateTime;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class ModuleConfig extends AbstractHelper
{
    const string SECTION = 'ai_integration/automatic_translation';

    const string GENERAL_GROUP = self::SECTION . '/general';
    const string CATALOG_GROUP = self::SECTION . '/catalog';
    const string ENGINE_GROUP = self::SECTION . '/translations_engine';

    const string ENABLE = self::GENERAL_GROUP . '/enable';
    const string SOURCE_LANGUAGE = self::GENERAL_GROUP . '/source_language';
    const string DESTINATION_LANGUAGE = 'general/locale/code';

    const string TXT_PRODUCT_ATTR = self::CATALOG_GROUP . '/text_attribute_to_translate';
    const string SELECT_PRODUCT_ATTR = self::CATALOG_GROUP . '/select_attribute_to_translate';
    const string ENABLE_PERIODIC = self::CATALOG_GROUP . '/enable_periodic_retranslation';
    const string RETRANSLATION_PERIOD = self::CATALOG_GROUP . '/retranslation_period';
    const string TRANSLATE_DISABLED = self::CATALOG_GROUP . '/translate_disabled';
    const string ENABLE_URL_REWRITE = self::CATALOG_GROUP . '/enable_url_rewrite';

    const string ENGINE = self::ENGINE_GROUP . '/engine';
    const string DEEPL_AUTH_KEY = self::ENGINE_GROUP . '/deepl_auth_key';
    const string OPEN_AI_ORG_ID = self::ENGINE_GROUP . '/openai_org_id';
    const string OPEN_AI_API_KEY = self::ENGINE_GROUP . '/openai_api_key';
    const string OPEN_AI_PROJECT_ID = self::ENGINE_GROUP . '/openai_project_id';
    const string OPEN_AI_MODEL = self::ENGINE_GROUP . '/openai_model';
    const string GEMINI_API_KEY = self::ENGINE_GROUP . '/gemini_api_key';
    const string GEMINI_MODEL = self::ENGINE_GROUP . '/gemini_model';

    /**
     * @param EncryptorInterface $encryptor
     * @param Context $context
     */
    public function __construct(
        protected EncryptorInterface $encryptor,
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isEnable(int $storeId = 0): bool
    {
        return $this->scopeConfig->isSetFlag(self::ENABLE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getSourceLanguage(int $storeId = 0): string
    {
        return (string)$this->scopeConfig->getValue(self::SOURCE_LANGUAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getDestinationLanguage(int $storeId = 0): string
    {
        return (string)$this->scopeConfig->getValue(self::DESTINATION_LANGUAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function getProductTxtAttributeToTranslate(int $storeId = 0): array
    {
        $attributes = $this->scopeConfig->getValue(self::TXT_PRODUCT_ATTR, ScopeInterface::SCOPE_STORE, $storeId);
        return (empty($attributes)) ? [] : explode(',', $attributes);
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function getProductSelectAttributeToTranslate(int $storeId = 0): array
    {
        $attributes = $this->scopeConfig->getValue(self::SELECT_PRODUCT_ATTR, ScopeInterface::SCOPE_STORE, $storeId);
        return (empty($attributes)) ? [] : explode(',', $attributes);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isEnablePeriodicRetranslation(int $storeId = 0): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::ENABLE_PERIODIC, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function translateDisabledProducts(int $storeId = 0): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::TRANSLATE_DISABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function enableUrlRewrite(int $storeId = 0): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::ENABLE_URL_REWRITE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getTranslationExpirationDate(int $storeId = 0): string
    {
        $retranslationDays = (string)$this->scopeConfig->getValue(
            self::RETRANSLATION_PERIOD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $translationExpirationDate = new DateTime();
        $translationExpirationDate->modify('-' . $retranslationDays . ' days');

        return $translationExpirationDate->format('Y-m-d H:i:s');
    }

    /**
     * @return string
     */
    public function getEngineForTranslation(): string
    {
        return (string)$this->scopeConfig->getValue(self::ENGINE, ScopeInterface::SCOPE_STORE, 0);
    }

    /**
     * @return string
     */
    public function getDeepLAuthKey(): string
    {
        $encryptedAuthKey = (string)$this->scopeConfig->getValue(self::DEEPL_AUTH_KEY, ScopeInterface::SCOPE_STORE, 0);
        return $this->encryptor->decrypt($encryptedAuthKey);
    }

    /**
     * @return string
     */
    public function getOpenAIApiKey(): string
    {
        $encryptedApiKey = (string)$this->scopeConfig->getValue(self::OPEN_AI_API_KEY, ScopeInterface::SCOPE_STORE, 0);
        return $this->encryptor->decrypt($encryptedApiKey);
    }

    /**
     * @return string
     */
    public function getOpenAIProjectId(): string
    {
        return (string)$this->scopeConfig->getValue(self::OPEN_AI_PROJECT_ID, ScopeInterface::SCOPE_STORE, 0);
    }

    /**
     * @return string
     */
    public function getOpenAIOrgID(): string
    {
        return (string)$this->scopeConfig->getValue(self::OPEN_AI_ORG_ID, ScopeInterface::SCOPE_STORE, 0);
    }

    /**
     * @return string
     */
    public function getOpenAIModel(): string
    {
        return (string)$this->scopeConfig->getValue(self::OPEN_AI_MODEL, ScopeInterface::SCOPE_STORE, 0);
    }

    /**
     * @return string
     */
    public function getGeminiApiKey(): string
    {
        $encryptedApiKey = (string)$this->scopeConfig->getValue(self::GEMINI_API_KEY, ScopeInterface::SCOPE_STORE, 0);
        return $this->encryptor->decrypt($encryptedApiKey);
    }

    /**
     * @return string
     */
    public function getGeminiModel(): string
    {
        return (string)$this->scopeConfig->getValue(self::GEMINI_MODEL, ScopeInterface::SCOPE_STORE, 0);
    }
}
