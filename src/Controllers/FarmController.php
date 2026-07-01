<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Repositories\FarmRepository;
use FarmQ\Services\AuthService;
use FarmQ\Services\FarmContext;
use FarmQ\Services\FarmGeometryService;
use FarmQ\Services\GovernorateService;

final class FarmController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private FarmContext $farmContext = new FarmContext(),
        private FarmRepository $farms = new FarmRepository(),
        private FarmGeometryService $geometry = new FarmGeometryService()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAuth();
        $farms = $this->farmContext->listForUser($user);

        return view('farms/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('farms.title'),
            'farms' => $farms,
        ]));
    }

    public function showCreate(): string
    {
        $user = $this->auth->requireAuth();

        return view('farms/create', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('farms.create_title'),
            'errors' => flash('errors') ?? [],
            'old' => flash('old') ?? [],
            'governorates' => GovernorateService::all(),
        ]));
    }

    public function create(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();

        $name = trim($_POST['name'] ?? '');
        $region = $_POST['region'] ?? '';
        $governorate = trim($_POST['governorate'] ?? '') ?: null;
        $validRegions = ['delta', 'upper_egypt', 'reclaimed_desert'];
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'required';
        }
        if (!in_array($region, $validRegions, true)) {
            $errors['region'] = 'invalid';
        }
        if ($governorate !== null && !GovernorateService::isValidForRegion($region, $governorate)) {
            $errors['governorate'] = 'invalid_governorate';
        }

        if ($errors !== []) {
            flash('errors', $errors);
            flash('old', ['name' => $name, 'region' => $region, 'governorate' => $governorate ?? '']);
            redirect('/farms/create?lang=' . $this->t->locale());
        }

        $farmId = $this->farms->create((int) $user['id'], $name, $region, $governorate);
        $_SESSION['active_farm_id'] = $farmId;

        flash('success', $this->t->get('farms.created'));
        redirect('/farms/boundary?lang=' . $this->t->locale());
    }

    public function boundary(): string
    {
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireOwnedActiveFarm($user);
        $center = $this->geometry->centroidForFarm($activeFarm);
        $polygon = $this->geometry->decodePolygon($activeFarm['polygon_geojson'] ?? null);

        return view('farms/boundary', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('farms.boundary_title'),
            'activeFarm' => $activeFarm,
            'center' => $center,
            'polygon' => $polygon,
            'boundaryError' => flash('boundary_error'),
        ]));
    }

    public function saveBoundary(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireOwnedActiveFarm($user);

        $raw = $_POST['polygon_geojson'] ?? '';
        if ($raw === '') {
            flash('boundary_error', 'missing_polygon');
            redirect('/farms/boundary?lang=' . $this->t->locale());
        }

        $validated = $this->geometry->validateAndNormalize($raw);
        if (!$validated['ok']) {
            flash('boundary_error', $validated['error'] ?? 'invalid_polygon');
            redirect('/farms/boundary?lang=' . $this->t->locale());
        }

        $this->farms->updatePolygon((int) $activeFarm['id'], (int) $user['id'], $validated['geojson']);
        flash('success', $this->t->get('farms.boundary_saved', ['area' => round($validated['area_ha'] ?? 0, 2)]));
        redirect('/map?lang=' . $this->t->locale());
    }

    public function switch(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();
        $farmId = (int) ($_POST['farm_id'] ?? 0);

        if (!$this->farmContext->switch($farmId, $user)) {
            flash('errors', ['farm' => 'not_found']);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard?lang=' . $this->t->locale();
        redirect($referer);
    }

    /** @param array<string, mixed> $user */
    private function requireOwnedActiveFarm(array $user): array
    {
        $farm = $this->farmContext->active($user);
        if ($farm === null) {
            flash('errors', ['farm' => 'required']);
            redirect('/farms/create?lang=' . $this->t->locale());
        }

        if ((int) ($farm['owner_user_id'] ?? 0) !== (int) $user['id']) {
            flash('boundary_error', 'not_owner');
            redirect('/farms?lang=' . $this->t->locale());
        }

        return $farm;
    }
}
