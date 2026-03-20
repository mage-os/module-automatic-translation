<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class TextAttributes implements OptionSourceInterface
{
    const GALLERY_ALT_ATTRIBUTE_CODE = 'gallery_alt';
    const GALLERY_ALT_IMAGE_ATTRIBUTE = [
        'value' => self::GALLERY_ALT_ATTRIBUTE_CODE,
        'label' => 'Gallery image alt text'
    ];
    const ATTRIBUTE_TYPES = [
        'text',
        'textarea'
    ];
    const ATTRIBUTES_TO_EXCLUDE = [
        'sku',
        'tier_price',
        'category_ids',
        'custom_layout_update',
        'image_label',
        'small_image_label',
        'thumbnail_label'
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

        $attributesArray = array_map(
            fn($attribute) => ['value' => $attribute->getAttributeCode(), 'label' => $attribute->getFrontendLabel()],
            $attributes
        );

        $attributesArray[] = self::GALLERY_ALT_IMAGE_ATTRIBUTE;

        return $attributesArray;
    }
}
