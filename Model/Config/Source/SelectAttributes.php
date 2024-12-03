<?php

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

/**
 * Class SelectAttributes
 */
class SelectAttributes implements OptionSourceInterface
{
    // Attribute types to select
    private const ATTRIBUTE_TYPES = [
        'select',
        'multiselect'
    ];
    // Attributes to exclude from the select
    private const ATTRIBUTES_TO_EXCLUDE = [
        'custom_design',
        'custom_layout',
        'custom_layout_update_file',
        'page_layout'
    ];

    protected CollectionFactory $collectionFactory;

    /**
     * SelectAttributes constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $attributesArray = [];

        $attributes = $this->collectionFactory
            ->create()
            ->addFieldToSelect('attribute_code')
            ->addFieldToSelect('frontend_label')
            ->addFieldToFilter('attribute_code', array('nin' => self::ATTRIBUTES_TO_EXCLUDE))
            ->addFieldToFilter('frontend_input', array('in' => self::ATTRIBUTE_TYPES))
            ->setOrder('frontend_label','ASC')
            ->getItems();

        foreach ($attributes as $attribute) {
            $attributesArray[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getFrontendLabel()
            ];
        }

        return $attributesArray;
    }
}
