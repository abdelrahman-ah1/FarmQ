<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Repositories\SoilSampleRepository;
use FarmQ\Services\AlertDigestService;
use FarmQ\Services\AlertService;
use FarmQ\Services\AuthService;
use FarmQ\Services\FarmContext;
use FarmQ\Services\IrrigationService;
use FarmQ\Services\TierGate;
use FarmQ\Services\WeatherForecastService;

final class AlertController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private FarmContext $farmContext = new FarmContext(),
        private WeatherForecastService $weather = new WeatherForecastService(),
        private AlertService $alerts = new AlertService(),
        private IrrigationService $irrigation = new IrrigationService(),
        private SoilSampleRepository $samples = new SoilSampleRepository(),
        private AlertDigestService $digest = new AlertDigestService()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireActiveFarm($user);
        $tierGate = new TierGate($activeFarm);

        if (!$tierGate->can('alerts')) {
            return view('alerts/upgrade', app_view_data($this->t, $user, [
                'pageTitle' => $this->t->get('alerts_page.title'),
                'activeFarm' => $activeFarm,
            ]));
        }

        $forecast = $this->weather->fetchForRegion((string) $activeFarm['region'], (int) $activeFarm['id']);
        $days = $forecast['days'] ?? [];
        $schedule = $this->irrigation->latest((int) $activeFarm['id']);
        $latestSample = $this->samples->latestForFarm((int) $activeFarm['id']);

        $alertList = $this->alerts->evaluate($days, $latestSample, [
            'crop_code' => (string) ($activeFarm['selected_crop_code'] ?? ''),
            'region' => (string) $activeFarm['region'],
            'governorate' => (string) ($activeFarm['governorate'] ?? ''),
            'schedule' => $schedule,
        ]);

        $this->digest->record($activeFarm, $alertList, $this->t);

        // Which categories actually appear in the current alert set (drives the filter bar).
        $availableCats = [];
        foreach ($alertList as $alert) {
            $availableCats[$alert['category']] = true;
        }

        $sevFilter = $this->normalizeFilter($_GET['sev'] ?? 'all', ['all', 'high', 'moderate', 'low']);
        $catFilter = $this->normalizeFilter($_GET['cat'] ?? 'all', array_merge(['all'], array_keys($availableCats)));

        $filtered = array_values(array_filter($alertList, static function (array $alert) use ($sevFilter, $catFilter): bool {
            if ($sevFilter !== 'all' && $alert['severity'] !== $sevFilter) {
                return false;
            }
            if ($catFilter !== 'all' && $alert['category'] !== $catFilter) {
                return false;
            }

            return true;
        }));

        $grouped = ['high' => [], 'moderate' => [], 'low' => []];
        foreach ($filtered as $alert) {
            $grouped[$alert['severity']][] = $alert;
        }

        return view('alerts/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('alerts_page.title'),
            'activeFarm' => $activeFarm,
            'alertList' => $filtered,
            'grouped' => $grouped,
            'counts' => $this->alerts->counts($alertList),
            'forecast' => array_slice($days, 0, 7),
            'availableCats' => array_keys($availableCats),
            'sevFilter' => $sevFilter,
            'catFilter' => $catFilter,
            'hasFilter' => $sevFilter !== 'all' || $catFilter !== 'all',
        ]));
    }

    /** @param array<int, string> $allowed */
    private function normalizeFilter(mixed $value, array $allowed): string
    {
        $value = is_string($value) ? $value : 'all';

        return in_array($value, $allowed, true) ? $value : 'all';
    }

    /** @param array<string, mixed> $user */
    private function requireActiveFarm(array $user): array
    {
        $farm = $this->farmContext->active($user);
        if ($farm === null) {
            flash('errors', ['farm' => 'required']);
            redirect('/farms/create?lang=' . $this->t->locale());
        }

        return $farm;
    }
}
