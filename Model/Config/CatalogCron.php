<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Model\Config;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Exception;

class CatalogCron extends Value
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $valueFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param string $modelPath
     * @param string $expression
     * @param string $cronStringPath
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        protected ValueFactory $valueFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        string $modelPath = '',
        protected string $expression = 'groups/catalog/fields/product_translation_cron/value',
        protected string $cronStringPath = 'crontab/translate_products/jobs/mageos_translate_products/schedule/cron_expr',
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return CatalogCron
     * @throws Exception
     */
    public function afterSave(): static
    {
        $expression = $this->getData($this->expression);

        try {
            $this->valueFactory->create()->load(
                $this->cronStringPath,
                'path'
            )->setValue(
                $expression
            )->setPath(
                $this->cronStringPath
            )->save();
        } catch (Exception $e) {
            throw new Exception(__('Unable to save the cron expression.'), 0, $e);
        }

        return parent::afterSave();
    }
}
