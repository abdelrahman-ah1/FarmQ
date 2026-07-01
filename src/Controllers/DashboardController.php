<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Repositories\SoilSampleRepository;
use FarmQ\Services\AlertService;
use FarmQ\Services\AuthService;
use FarmQ\Services\BlueprintService;
use FarmQ\Services\CropSelectionService;
use FarmQ\Services\FarmContext;
use FarmQ\Services\GeospatialJobService;
use FarmQ\Services\IrrigationService;
use FarmQ\Services\OnboardingService;
use FarmQ\Services\TierGate;
use FarmQ\Services\WeatherForecastService;

final class DashboardController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private FarmContext $farmContext = new FarmContext(),
        private SoilSampleRepository $samples = new SoilSampleRepository(),
        private CropSelectionService $crops = new CropSelectionService(),
        private BlueprintService $blueprints = new BlueprintService(),
        private GeospatialJobService $geo = new GeospatialJobService(),
        private WeatherForecastService $weather = new WeatherForecastService(),
        private AlertService $alerts = new AlertService(),
        private IrrigationService $irrigation = new IrrigationService(),
        private OnboardingService $onboarding = new OnboardingService()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAuth();
        $farms = $this->farmContext->listForUser($user);
        $activeFarm = $this->farmContext->active($user);
        $tierGate = $activeFarm !== null ? new TierGate($activeFarm) : null;
        $latestSample = $activeFarm ? $this->samples->latestForFarm((int) $activeFarm['id']) : null;
        $selectedCrop = $activeFarm ? $this->crops->selectedCrop($activeFarm, $this->t->locale()) : null;
        $planRow = $activeFarm ? $this->blueprints->latestPlan((int) $activeFarm['id']) : null;
        $planStale = $activeFarm ? $this->blueprints->isStale($activeFarm, $planRow) : true;
        $mapData = ($activeFarm && $tierGate?->can('satellite'))
            ? $this->geo->getMapData($activeFarm)
            : null;

        $forecast = null;
        $alertList = [];
        $irrigationSchedule = null;
        if ($activeFarm && $tierGate?->can('forecast')) {
            $forecastResult = $this->weather->fetchForRegion((string) $activeFarm['region'], (int) $activeFarm['id']);
            $forecast = array_slice($forecastResult['days'] ?? [], 0, 3);
            $alertList = $this->alerts->evaluate(
                $forecastResult['days'] ?? [],
                $latestSample,
                (string) ($activeFarm['selected_crop_code'] ?? '')
            );
        }
        if ($activeFarm && $tierGate?->can('irrigation')) {
            $irrigationSchedule = $this->irrigation->latest((int) $activeFarm['id']);
        }

        $onboardingSteps = $this->onboarding->steps(
            $activeFarm !== null,
            $latestSample !== null,
            $selectedCrop !== null,
            $planRow !== null && !$planStale
        );

        return view('dashboard/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('dashboard.title'),
            'farms' => $farms,
            'activeFarm' => $activeFarm,
            'tierGate' => $tierGate,
            'latestSample' => $latestSample,
            'selectedCrop' => $selectedCrop,
            'planRow' => $planRow,
            'planStale' => $planStale,
            'mapData' => $mapData,
            'forecast' => $forecast,
            'alertList' => $alertList,
            'irrigationSchedule' => $irrigationSchedule,
            'onboardingSteps' => $onboardingSteps,
            'onboardingComplete' => $this->onboarding->isComplete($onboardingSteps),
        ]));
    }
}
