<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;

final class LocaleController
{
    public function __construct(private Translator $t)
    {
    }

    public function switch(string $locale): never
    {
        $supported = explode(',', env('SUPPORTED_LOCALES', 'en,ar'));
        if (in_array($locale, $supported, true)) {
            $_SESSION['locale'] = $locale;
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $url = parse_url($referer);
        $path = $url['path'] ?? '/';
        redirect($path . '?lang=' . $locale);
    }
}
