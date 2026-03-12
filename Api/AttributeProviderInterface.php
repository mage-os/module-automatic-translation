<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Api;

/**
 * Interface AttributeProviderInterface
 */
interface AttributeProviderInterface
{
    const string SKIP_TRANSLATION = 'skip_translation';
    const string SKIP_TRANSLATION_LABEL = 'Skip translation';
    const string SKIP_TRANSLATION_NOTE = 'Uncheck to re-translate';
    const string LAST_TRANSLATION = 'last_translation_date';
    const string LAST_TRANSLATION_LABEL = 'Last translation date';
}
