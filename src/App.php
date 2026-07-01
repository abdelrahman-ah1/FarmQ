<?php

declare(strict_types=1);

namespace FarmQ;

use FarmQ\Controllers\AlertController;
use FarmQ\Controllers\AuthController;
use FarmQ\Controllers\BillingController;
use FarmQ\Controllers\DashboardController;
use FarmQ\Controllers\FarmController;
use FarmQ\Controllers\LandingController;
use FarmQ\Controllers\LocaleController;
use FarmQ\Controllers\BlueprintController;
use FarmQ\Controllers\IngestionController;
use FarmQ\Controllers\HistoryController;
use FarmQ\Controllers\IrrigationController;
use FarmQ\Controllers\MapController;
use FarmQ\Controllers\PortfolioController;
use FarmQ\Controllers\SettingsController;
use FarmQ\Localization\Translator;
use FarmQ\Services\Database;

final class App
{
    private Router $router;
    private Translator $translator;

    private function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->translator = new Translator();
        $this->router = new Router();
        $this->registerRoutes();
    }

    public static function create(): self
    {
        return new self();
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $this->router->dispatch($method, $uri);
    }

    public function translator(): Translator
    {
        return $this->translator;
    }

    private function registerRoutes(): void
    {
        $landing = new LandingController($this->translator);
        $auth = new AuthController($this->translator);
        $dashboard = new DashboardController($this->translator);
        $farms = new FarmController($this->translator);
        $map = new MapController($this->translator);
        $ingestion = new IngestionController($this->translator);
        $blueprint = new BlueprintController($this->translator);
        $irrigation = new IrrigationController($this->translator);
        $alerts = new AlertController($this->translator);
        $billing = new BillingController($this->translator);
        $history = new HistoryController($this->translator);
        $portfolio = new PortfolioController($this->translator);
        $settings = new SettingsController($this->translator);
        $locale = new LocaleController($this->translator);

        $this->router->get('/', [$landing, 'index']);
        $this->router->get('/demo', [$landing, 'demo']);
        $this->router->post('/demo', [$landing, 'submitDemo']);

        $this->router->get('/login', [$auth, 'showLogin']);
        $this->router->post('/login', [$auth, 'login']);
        $this->router->get('/signup', [$auth, 'showSignup']);
        $this->router->post('/signup', [$auth, 'signup']);
        $this->router->get('/logout', [$auth, 'logout']);

        $this->router->get('/dashboard', [$dashboard, 'index']);
        $this->router->get('/farms', [$farms, 'index']);
        $this->router->get('/farms/create', [$farms, 'showCreate']);
        $this->router->post('/farms/create', [$farms, 'create']);
        $this->router->get('/farms/boundary', [$farms, 'boundary']);
        $this->router->post('/farms/boundary', [$farms, 'saveBoundary']);
        $this->router->post('/farms/switch', [$farms, 'switch']);
        $this->router->get('/map', [$map, 'index']);
        $this->router->post('/map/scan', [$map, 'scan']);
        $this->router->get('/ingestion', [$ingestion, 'index']);
        $this->router->post('/ingestion/upload', [$ingestion, 'upload']);
        $this->router->post('/ingestion/crop', [$ingestion, 'selectCrop']);

        $this->router->get('/blueprint', [$blueprint, 'index']);
        $this->router->post('/blueprint/generate', [$blueprint, 'generate']);
        $this->router->get('/blueprint/export', [$blueprint, 'export']);
        $this->router->get('/blueprint/print', [$blueprint, 'print']);

        $this->router->get('/irrigation', [$irrigation, 'index']);
        $this->router->post('/irrigation/generate', [$irrigation, 'generate']);

        $this->router->get('/alerts', [$alerts, 'index']);

        $this->router->get('/billing', [$billing, 'index']);
        $this->router->post('/billing/checkout', [$billing, 'checkout']);
        $this->router->get('/billing/callback', [$billing, 'callback']);
        $this->router->post('/billing/webhook', [$billing, 'webhook']);
        $this->router->get('/billing/mock', [$billing, 'mock']);
        $this->router->post('/billing/mock/complete', [$billing, 'mockComplete']);

        $this->router->get('/history', [$history, 'index']);
        $this->router->get('/portfolio', [$portfolio, 'index']);
        $this->router->get('/portfolio/accept', [$portfolio, 'accept']);
        $this->router->post('/portfolio/link', [$portfolio, 'link']);
        $this->router->get('/portfolio/report', [$portfolio, 'report']);

        $this->router->post('/farms/invite', [$farms, 'invite']);
        $this->router->get('/settings', [$settings, 'index']);

        $this->router->get('/locale/{locale}', [$locale, 'switch']);
    }

    public static function db(): Database
    {
        static $db = null;
        if ($db === null) {
            $db = Database::connect(
                env('DB_HOST', '127.0.0.1'),
                (int) env('DB_PORT', '3306'),
                env('DB_NAME', 'farmq'),
                env('DB_USER', 'farmq'),
                env('DB_PASS', 'farmq_secret')
            );
        }
        return $db;
    }
}
