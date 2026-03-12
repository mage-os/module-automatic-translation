<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean as AttributeBoolean;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageOS\AutomaticTranslation\Api\AttributeProviderInterface as AttributeProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\ValidateException;

class AddSkipTranslationProductAttribute implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected EavSetupFactory $eavSetupFactory
    ) {
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

        $eavSetup->removeAttribute(Product::ENTITY, AttributeProvider::SKIP_TRANSLATION);
        $eavSetup->addAttribute(
            Product::ENTITY,
            AttributeProvider::SKIP_TRANSLATION,
            [
                'is_visible_in_grid' => true,
                'is_html_allowed_on_front' => false,
                'visible_on_front' => false,
                'visible' => true,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'label' => AttributeProvider::SKIP_TRANSLATION_LABEL,
                'source' => AttributeBoolean::class,
                'type' => 'int',
                'is_used_in_grid' => true,
                'required' => false,
                'input' => 'boolean',
                'is_filterable_in_grid' => true,
                'sort_order' => 10,
                'group' => 'Product Details',
                'note' => AttributeProvider::SKIP_TRANSLATION_NOTE
            ]
        );
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
