<?php

namespace MageOS\AutomaticTranslation\Model\Config;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\Config\ValueFactory;
use Exception;

/**
 * Class CatalogCron
 */
class CatalogCron extends Value
{
    /**
     * @var ValueFactory
     */
    protected $valueFactory;
    /**
     * @var string
     */
    protected $expression;
    /**
     * @var string
     */
    protected $cronStringPath;

    /**
     * CatalogCron constructor.
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
        ValueFactory $valueFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        string $modelPath = '',
        string $expression = 'groups/catalog/fields/product_translation_cron/value',
        string $cronStringPath = 'crontab/translate_products/jobs/mageos_translate_products/schedule/cron_expr',
        array $data = []
    ) {
        $this->valueFactory = $valueFactory;
        $this->expression = $expression;
        $this->cronStringPath = $cronStringPath;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return CronStringExpr
     * @throws Exception
     */
    public function afterSave()
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
            throw new Exception(__('Some Thing Want Wrong , We can\'t save the cron expression.'));
        }

        return parent::afterSave();
    }
}
