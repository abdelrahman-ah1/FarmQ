<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Services\AuthService;
use FarmQ\Services\PortfolioService;

final class SettingsController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private PortfolioService $portfolio = new PortfolioService()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAdmin();
        $stats = $this->portfolio->systemStats();

        return view('settings/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('settings.title'),
            'stats' => $stats,
            'config' => [
                'app_url' => env('APP_URL', ''),
                'app_env' => env('APP_ENV', 'local'),
                'default_locale' => env('DEFAULT_LOCALE', 'ar'),
                'payment_gateway' => env('PAYMENT_GATEWAY', 'paymob'),
                'payment_key_set' => env('PAYMENT_GATEWAY_API_KEY', '') !== '',
                'dev_unlock_paid' => env('DEV_UNLOCK_PAID', '0') === '1',
                'redis_host' => env('REDIS_HOST', '127.0.0.1'),
            ],
        ]));
    }
}
