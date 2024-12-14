<?php

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

/**
 * Class TextAttributes
 */
class TextAttributes implements OptionSourceInterface
{
    // Custom attribute for gallery alt images translations management
    public const GALLERY_ALT_ATTRIBUTE_CODE = 'gallery_alt';
    private const GALLERY_ALT_IMAGE_ATTRIBUTE = [
        'value' => self::GALLERY_ALT_ATTRIBUTE_CODE,
        'label' => 'Gallery image alt text'
    ];

    // Attribute types to select
    private const ATTRIBUTE_TYPES = [
        'text',
        'textarea'
    ];
    // Attributes to exclude from the select
    private const ATTRIBUTES_TO_EXCLUDE = [
        'sku',
        'tier_price',
        'category_ids',
        'custom_layout_update',
        'image_label',
        'small_image_label',
        'thumbnail_label'
    ];

    protected CollectionFactory $collectionFactory;

    /**
     * TextAttributes constructor.
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
            ->setOrder('frontend_label', 'ASC')
            ->getItems();

        foreach ($attributes as $attribute) {
            $attributesArray[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getFrontendLabel()
            ];
        }

        $attributesArray[] = self::GALLERY_ALT_IMAGE_ATTRIBUTE;

        return $attributesArray;
    }
}
