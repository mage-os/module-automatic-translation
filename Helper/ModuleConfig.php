<?php

namespace MageOS\AutomaticTranslation\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ModuleConfig
 * @package MageOS\AutomaticTranslation\Helper
 */
class ModuleConfig extends AbstractHelper
{
    protected const SECTION = 'automatic_translation';

    protected const GENERAL_GROUP = self::SECTION . '/general';
    protected const CATALOG_GROUP = self::SECTION . '/catalog';
    protected const ENGINE_GROUP = self::SECTION . '/translations_engine';

    protected const ENABLE = self::GENERAL_GROUP . '/enable';
    protected const SOURCE_LANGUAGE = self::GENERAL_GROUP . '/source_language';
    protected const DESTINATION_LANGUAGE = 'general/locale/code';

    protected const TXT_PRODUCT_ATTR = self::CATALOG_GROUP . '/text_attribute_to_translate';
    protected const SELECT_PRODUCT_ATTR = self::CATALOG_GROUP . '/select_attribute_to_translate';
    protected const ENABLE_PERIODIC = self::CATALOG_GROUP . '/enable_periodic_retranslation';
    protected const RETRANSLATION_PERIOD = self::CATALOG_GROUP . '/retranslation_period';
    protected const TRANSLATE_DISABLED = self::CATALOG_GROUP . '/translate_disabled';
    protected const ENABLE_URL_REWRITE = self::CATALOG_GROUP . '/enable_url_rewrite';

    protected const ENGINE = self::ENGINE_GROUP . '/engine';
    protected const DEEPL_AUTH_KEY = self::ENGINE_GROUP . '/deepl_auth_key';
    protected const OPEN_AI_ORG_ID = self::ENGINE_GROUP . '/openai_org_id';
    protected const OPEN_AI_API_KEY = self::ENGINE_GROUP . '/openai_api_key';
    protected const OPEN_AI_PROJECT_ID = self::ENGINE_GROUP . '/openai_project_id';
    protected const OPEN_AI_MODEL = self::ENGINE_GROUP . '/openai_model';
    protected const GEMINI_API_KEY = self::ENGINE_GROUP . '/gemini_api_key';
    protected const GEMINI_MODEL = self::ENGINE_GROUP . '/gemini_model';

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
    public function getTranslationExpirationDate(int $storeId = 0)
    {
        $retranslationDays = (string)$this->scopeConfig->getValue(self::RETRANSLATION_PERIOD, ScopeInterface::SCOPE_STORE, $storeId);

        $translationExpirationDate = new \DateTime();
        $translationExpirationDate->setTimestamp(strtotime('now'));
        $translationExpirationDate->modify('-' . $retranslationDays . ' days');

        return date('Y-m-d H:i:s', $translationExpirationDate->getTimestamp());
    }

    /**
     * @return string
     */
    public function getEngineForTranslation()
    {
        return (string)$this->scopeConfig->getValue(self::ENGINE, ScopeInterface::SCOPE_STORE, 0);
    }

    /**
     * @return string
     */
    public function getDeepLAuthKey(): string
    {
        return (string)$this->scopeConfig->getValue(self::DEEPL_AUTH_KEY, ScopeInterface::SCOPE_STORE, 0);
    }

    /**
     * @return string
     */
    public function getOpenAIApiKey(): string
    {
        return (string)$this->scopeConfig->getValue(self::OPEN_AI_API_KEY, ScopeInterface::SCOPE_STORE, 0);
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
        return (string)$this->scopeConfig->getValue(self::GEMINI_API_KEY, ScopeInterface::SCOPE_STORE, 0);
    }

    /**
     * @return string
     */
    public function getGeminiModel(): string
    {
        return (string)$this->scopeConfig->getValue(self::GEMINI_MODEL, ScopeInterface::SCOPE_STORE, 0);
    }
}
