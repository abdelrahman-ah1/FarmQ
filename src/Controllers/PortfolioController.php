<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Services\AuthService;
use FarmQ\Services\FarmContext;
use FarmQ\Services\PortfolioService;
use FarmQ\Services\TierGate;

final class PortfolioController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private FarmContext $farmContext = new FarmContext(),
        private PortfolioService $portfolio = new PortfolioService()
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
        $summaries = $this->portfolio->summarize($farms);

        return view('portfolio/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('portfolio.title'),
            'summaries' => $summaries,
            'tierGate' => $tierGate,
        ]));
    }

    public function link(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();

        if (!$this->auth->isAgronomist($user)) {
            redirect('/dashboard?lang=' . $this->t->locale());
        }

        $farms = $this->farmContext->listForUser($user);
        $tierGate = new TierGate($farms[0] ?? ['tier' => 'free']);
        if (!$tierGate->can('multi_farm')) {
            flash('portfolio_error', 'paid_required');
            redirect('/portfolio?lang=' . $this->t->locale());
        }

        $farmId = (int) ($_POST['farm_id'] ?? 0);
        if ($farmId <= 0 || !$this->farmContext->grantConsultantAccess($farmId, (int) $user['id'])) {
            flash('portfolio_error', 'link_failed');
        } else {
            flash('success', $this->t->get('portfolio.linked'));
            $_SESSION['active_farm_id'] = $farmId;
        }

        redirect('/portfolio?lang=' . $this->t->locale());
    }
}
