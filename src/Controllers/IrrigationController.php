<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Repositories\SoilSampleRepository;
use FarmQ\Services\AlertService;
use FarmQ\Services\AuthService;
use FarmQ\Services\FarmContext;
use FarmQ\Services\IrrigationService;
use FarmQ\Services\TierGate;
use FarmQ\Services\WeatherForecastService;

final class IrrigationController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private FarmContext $farmContext = new FarmContext(),
        private IrrigationService $irrigation = new IrrigationService(),
        private WeatherForecastService $weather = new WeatherForecastService(),
        private AlertService $alerts = new AlertService(),
        private SoilSampleRepository $samples = new SoilSampleRepository()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireActiveFarm($user);
        $tierGate = new TierGate($activeFarm);

        if (!$tierGate->can('irrigation')) {
            return view('irrigation/upgrade', app_view_data($this->t, $user, [
                'pageTitle' => $this->t->get('irrigation.title'),
                'activeFarm' => $activeFarm,
            ]));
        }

        $cropCode = $activeFarm['selected_crop_code'] ?? null;
        $schedule = $this->irrigation->latest((int) $activeFarm['id']);
        $forecast = $this->weather->fetchForRegion((string) $activeFarm['region'], (int) $activeFarm['id']);
        $alertList = $this->alerts->evaluate(
            $forecast['days'] ?? [],
            $this->samples->latestForFarm((int) $activeFarm['id']),
            (string) ($cropCode ?? '')
        );

        return view('irrigation/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('irrigation.title'),
            'activeFarm' => $activeFarm,
            'schedule' => $schedule,
            'forecast' => $forecast['days'] ?? [],
            'alerts' => $alertList,
            'cropCode' => $cropCode,
        ]));
    }

    public function generate(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireActiveFarm($user);
        $tierGate = new TierGate($activeFarm);

        if (!$tierGate->can('irrigation')) {
            redirect('/irrigation?lang=' . $this->t->locale());
        }

        $this->irrigation->generate($activeFarm, $activeFarm['selected_crop_code'] ?? null);
        flash('success', $this->t->get('irrigation.generated'));
        redirect('/irrigation?lang=' . $this->t->locale());
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
