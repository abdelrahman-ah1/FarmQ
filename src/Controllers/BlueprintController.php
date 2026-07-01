<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Services\AuthService;
use FarmQ\Services\BlueprintService;
use FarmQ\Services\ExportBranding;
use FarmQ\Services\FarmContext;
use FarmQ\Services\SeasonService;

final class BlueprintController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private FarmContext $farmContext = new FarmContext(),
        private BlueprintService $blueprints = new BlueprintService()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireActiveFarm($user);
        $readiness = $this->blueprints->readiness($activeFarm);
        $planRow = $this->blueprints->latestPlan((int) $activeFarm['id']);
        $isStale = $this->blueprints->isStale($activeFarm, $planRow);
        $canEdit = (new \FarmQ\Services\FarmAccessService())->canEdit((int) $activeFarm['id'], (int) $user['id']);

        return view('blueprint/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('blueprint.title'),
            'activeFarm' => $activeFarm,
            'canEdit' => $canEdit,
            'readiness' => $readiness,
            'planRow' => $planRow,
            'isStale' => $isStale,
            'cropName' => $planRow ? $this->blueprints->cropDisplayName($planRow, $this->t->locale()) : null,
        ]));
    }

    public function generate(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();
        $activeFarm = $this->farmContext->requireActiveEditable($user, $this->t);

        $result = $this->blueprints->generateForFarm($activeFarm, $this->t->locale());
        if (!$result['ok']) {
            flash('blueprint_error', $result['error'] ?? 'unknown');
            redirect('/blueprint?lang=' . $this->t->locale());
        }

        flash('success', $this->t->get('blueprint.generated'));
        redirect('/blueprint?lang=' . $this->t->locale());
    }

    public function export(): never
    {
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireActiveFarm($user);
        $planRow = $this->blueprints->latestPlan((int) $activeFarm['id']);

        if ($planRow === null) {
            flash('blueprint_error', 'no_plan');
            redirect('/blueprint?lang=' . $this->t->locale());
        }

        $plan = $planRow['plan'];
        $season = $plan['metadata']['season'] ?? SeasonService::current();
        $filename = 'farmq_blueprint_' . $activeFarm['id'] . '_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            exit;
        }

        ExportBranding::writeCsvPreamble(
            $out,
            $this->t,
            $activeFarm,
            $this->blueprints->cropDisplayName($planRow, $this->t->locale()),
            (string) $planRow['generated_at'],
            $season
        );

        fputcsv($out, ['season', 'stage', 'urea_kg_ha', 'dap_kg_ha', 'potassium_sulfate_kg_ha']);
        foreach ($plan['schedule'] ?? [] as $row) {
            fputcsv($out, [
                $season,
                $row['stage'],
                $row['urea'],
                $row['dap'],
                $row['potassium_sulfate'],
            ]);
        }

        fclose($out);
        exit;
    }

    public function print(): string
    {
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireActiveFarm($user);
        $planRow = $this->blueprints->latestPlan((int) $activeFarm['id']);

        if ($planRow === null) {
            flash('blueprint_error', 'no_plan');
            redirect('/blueprint?lang=' . $this->t->locale());
        }

        return view('blueprint/print', [
            't' => $this->t,
            'activeFarm' => $activeFarm,
            'planRow' => $planRow,
            'plan' => $planRow['plan'],
            'cropName' => $this->blueprints->cropDisplayName($planRow, $this->t->locale()),
            'season' => $planRow['plan']['metadata']['season'] ?? SeasonService::current(),
        ]);
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
