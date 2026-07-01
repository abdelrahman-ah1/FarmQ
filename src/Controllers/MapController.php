<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Services\AuthService;
use FarmQ\Services\FarmContext;
use FarmQ\Services\GeospatialJobService;
use FarmQ\Services\TierGate;

final class MapController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private FarmContext $farmContext = new FarmContext(),
        private GeospatialJobService $geo = new GeospatialJobService()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireActiveFarm($user);
        $tierGate = new TierGate($activeFarm);
        $canAccess = $tierGate->can('satellite');
        $mapData = $canAccess
            ? $this->geo->getMapData($activeFarm)
            : null;

        return view('map/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('map.title'),
            'activeFarm' => $activeFarm,
            'tierGate' => $tierGate,
            'canAccess' => $canAccess,
            'mapData' => $mapData,
            'mapError' => flash('map_error'),
        ]));
    }

    public function scan(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireActiveFarm($user);
        $tierGate = new TierGate($activeFarm);

        if (!$tierGate->can('satellite')) {
            redirect('/map?lang=' . $this->t->locale());
        }

        $result = $this->geo->startScan($activeFarm);
        if (!$result['ok']) {
            flash('map_error', $result['error'] ?? 'unknown');
        } else {
            flash('success', $this->t->get('map.scan_started'));
        }

        redirect('/map?lang=' . $this->t->locale());
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
