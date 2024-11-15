<?php

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

class SelectAttributes implements OptionSourceInterface
{
    protected CollectionFactory $collectionFactory;


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
            ->addFieldToSelect('*')
            ->addFieldToFilter('frontend_input', ['select', 'multiselect'])
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
