<?php

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Config\Model\Config\Source\Locale as ConfigSourceLocale;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class LanguageList
 */
class LanguageList extends ConfigSourceLocale implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $emptyOption = ['value' => '', 'label' => __('-- Please Select --')];
        $originalList = parent::toOptionArray();

        array_unshift($originalList, $emptyOption);

        return $originalList;
    }
}
