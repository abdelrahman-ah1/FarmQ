<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Services\AuthService;
use FarmQ\Services\FarmContext;
use FarmQ\Services\HistoricalService;
use FarmQ\Services\TierGate;

final class HistoryController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private FarmContext $farmContext = new FarmContext(),
        private HistoricalService $history = new HistoricalService()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireActiveFarm($user);
        $tierGate = new TierGate($activeFarm);
        $data = $this->history->forFarm((int) $activeFarm['id'], $tierGate->can('satellite'));

        return view('history/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('history.title'),
            'activeFarm' => $activeFarm,
            'history' => $data,
            'tierGate' => $tierGate,
        ]));
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
