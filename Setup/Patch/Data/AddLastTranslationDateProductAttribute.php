<?php

namespace MageOS\AutomaticTranslation\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageOS\AutomaticTranslation\Api\AttributeProviderInterface as AttributeProvider;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\ValidateException;

/**
 * Class AddLastTranslationDateProductAttribute
 * @package MageOS\AutomaticTranslation\Setup\Patch\Data
 */
class AddLastTranslationDateProductAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    protected EavSetupFactory $eavSetupFactory;

    /**
     * AddLastTranslationDateProductAttribute constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function apply(): void
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->removeAttribute(Product::ENTITY, AttributeProvider::LAST_TRANSLATION);
        $eavSetup->addAttribute(
            Product::ENTITY,
            AttributeProvider::LAST_TRANSLATION,
            [
                'is_visible_in_grid' => true,
                'is_html_allowed_on_front' => false,
                'visible_on_front' => false,
                'visible' => true,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'label' => AttributeProvider::LAST_TRANSLATION_LABEL,
                'source' => null,
                'type' => 'datetime',
                'is_used_in_grid' => true,
                'required' => false,
                'input' => 'date',
                'is_filterable_in_grid' => true,
                'sort_order' => 11,
                'group' => 'Product Details',
            ]
        );
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
