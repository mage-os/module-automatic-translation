<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class SelectAttributes implements OptionSourceInterface
{
    const array ATTRIBUTE_TYPES = [
        'select',
        'multiselect'
    ];
    const array ATTRIBUTES_TO_EXCLUDE = [
        'custom_design',
        'custom_layout',
        'custom_layout_update_file',
        'page_layout',
        'msrp_display_actual_price_type',
        'price_view',
        'shipment_type',
        'gift_message_available'
    ];

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        protected CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $attributes = $this->collectionFactory
            ->create()
            ->addFieldToSelect('attribute_code')
            ->addFieldToSelect('frontend_label')
            ->addFieldToFilter('attribute_code', ['nin' => self::ATTRIBUTES_TO_EXCLUDE])
            ->addFieldToFilter('frontend_input', ['in' => self::ATTRIBUTE_TYPES])
            ->setOrder('frontend_label', 'ASC')
            ->getItems();

        return array_map(
            fn($attribute) => ['value' => $attribute->getAttributeCode(), 'label' => $attribute->getFrontendLabel()],
            $attributes
        );
    }
}
