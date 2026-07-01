<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Services\AuthService;
use FarmQ\Services\BlueprintService;
use FarmQ\Services\FarmAccessService;
use FarmQ\Services\FarmContext;
use FarmQ\Services\FarmInviteService;
use FarmQ\Services\PortfolioService;
use FarmQ\Services\SeasonService;
use FarmQ\Services\TierGate;

final class PortfolioController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private FarmContext $farmContext = new FarmContext(),
        private PortfolioService $portfolio = new PortfolioService(),
        private FarmInviteService $invites = new FarmInviteService(),
        private BlueprintService $blueprints = new BlueprintService(),
        private FarmAccessService $access = new FarmAccessService()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAuth();

        if (!$this->auth->isAgronomist($user)) {
            redirect('/dashboard?lang=' . $this->t->locale());
        }

        $farms = $this->farmContext->listForUser($user);
        $tierGate = new TierGate($farms[0] ?? ['tier' => 'free']);
        $filters = [
            'region' => trim((string) ($_GET['region'] ?? '')),
            'crop' => trim((string) ($_GET['crop'] ?? '')),
            'flag' => trim((string) ($_GET['flag'] ?? '')),
        ];
        $allSummaries = $this->portfolio->buildSummaries($farms, (int) $user['id']);
        $summaries = $this->portfolio->filter($allSummaries, $filters);

        return view('portfolio/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('portfolio.title'),
            'summaries' => $summaries,
            'tierGate' => $tierGate,
            'filters' => $filters,
            'cropOptions' => $this->portfolio->cropOptions($allSummaries),
            'totalCount' => count($allSummaries),
        ]));
    }

    public function accept(): never
    {
        $user = $this->auth->requireAuth();

        if (!$this->auth->isAgronomist($user)) {
            redirect('/dashboard?lang=' . $this->t->locale());
        }

        $code = trim((string) ($_GET['code'] ?? $_POST['invite_code'] ?? ''));
        if ($code === '') {
            flash('portfolio_error', 'invalid_invite');
            redirect('/portfolio?lang=' . $this->t->locale());
        }

        $farms = $this->farmContext->listForUser($user);
        $tierGate = new TierGate($farms[0] ?? ['tier' => 'free']);
        if (!$tierGate->can('multi_farm')) {
            flash('portfolio_error', 'paid_required');
            redirect('/portfolio?lang=' . $this->t->locale());
        }

        $result = $this->invites->accept($code, (int) $user['id']);
        if (!$result['ok']) {
            flash('portfolio_error', $result['error'] ?? 'link_failed');
            redirect('/portfolio?lang=' . $this->t->locale());
        }

        flash('success', $this->t->get('portfolio.linked'));
        $_SESSION['active_farm_id'] = (int) $result['farm_id'];
        redirect('/portfolio?lang=' . $this->t->locale());
    }

    public function link(): never
    {
        verify_csrf();
        $_GET['code'] = trim((string) ($_POST['invite_code'] ?? ''));
        $this->accept();
    }

    public function report(): string
    {
        $user = $this->auth->requireAuth();

        if (!$this->auth->isAgronomist($user)) {
            redirect('/dashboard?lang=' . $this->t->locale());
        }

        $farmId = (int) ($_GET['farm_id'] ?? 0);
        if ($farmId <= 0 || !$this->access->canView($farmId, (int) $user['id'])) {
            flash('portfolio_error', 'report_denied');
            redirect('/portfolio?lang=' . $this->t->locale());
        }

        $farm = $this->farmContext->listForUser($user);
        $activeFarm = null;
        foreach ($farm as $f) {
            if ((int) $f['id'] === $farmId) {
                $activeFarm = $f;
                break;
            }
        }
        if ($activeFarm === null) {
            flash('portfolio_error', 'report_denied');
            redirect('/portfolio?lang=' . $this->t->locale());
        }

        $planRow = $this->blueprints->latestPlan($farmId);
        if ($planRow === null) {
            flash('portfolio_error', 'no_plan');
            redirect('/portfolio?lang=' . $this->t->locale());
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
}
