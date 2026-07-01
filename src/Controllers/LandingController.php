<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;

use FarmQ\Services\DemoRequestService;

final class LandingController
{
    public function __construct(
        private Translator $t,
        private DemoRequestService $demoRequests = new DemoRequestService()
    ) {
    }

    public function index(): string
    {
        return view('landing', [
            't' => $this->t,
            'pageTitle' => $this->t->get('meta.title'),
        ]);
    }

    public function demo(): string
    {
        return view('auth/demo', [
            't' => $this->t,
            'pageTitle' => $this->t->get('auth.demo_title'),
        ]);
    }

    public function submitDemo(): never
    {
        verify_csrf();
        $result = $this->demoRequests->submit([
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'message' => $_POST['message'] ?? '',
            'locale' => $this->t->locale(),
        ]);

        if (!$result['ok']) {
            flash('demo_error', $result['error'] ?? 'unknown');
            redirect('/demo?lang=' . $this->t->locale());
        }

        flash('demo_success', '1');
        redirect('/demo?lang=' . $this->t->locale());
    }
}
