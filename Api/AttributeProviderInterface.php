<?php

namespace MageOS\AutomaticTranslation\Api;

/**
 * Interface AttributeProviderInterface
 */
interface AttributeProviderInterface
{
    public const SKIP_TRANSLATION = 'skip_translation';
    public const SKIP_TRANSLATION_LABEL = 'Skip translation';
    public const SKIP_TRANSLATION_NOTE = 'Uncheck to re-translate';
    public const LAST_TRANSLATION = 'last_translation_date';
    public const LAST_TRANSLATION_LABEL = 'Last translation date';
}
