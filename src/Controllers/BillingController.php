<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Repositories\FarmRepository;
use FarmQ\Services\AuthService;
use FarmQ\Services\PaymentGatewayService;
use FarmQ\Services\SubscriptionService;
use FarmQ\Services\TierGate;

final class BillingController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private SubscriptionService $subscriptions = new SubscriptionService(),
        private PaymentGatewayService $gateway = new PaymentGatewayService(),
        private FarmRepository $farms = new FarmRepository()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAuth();
        $owned = $this->farms->findByOwner((int) $user['id']);

        $farmStates = [];
        foreach ($owned as $farm) {
            $gate = new TierGate($farm);
            $farmStates[] = [
                'farm' => $farm,
                'is_paid' => $gate->isPaid(),
                'expires' => $farm['tier_expires_at'] ?? null,
            ];
        }

        return view('billing/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('billing.title'),
            'farmStates' => $farmStates,
            'price' => $this->subscriptions->priceEgp(),
            'seasonDays' => $this->subscriptions->seasonDays(),
            'rails' => SubscriptionService::RAILS,
            'isMock' => $this->gateway->isMock(),
            'gatewayName' => $this->gateway->gatewayName(),
            'history' => $this->subscriptions->history((int) $user['id']),
            'billingError' => flash('billing_error'),
        ]));
    }

    public function checkout(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();

        $farmId = (int) ($_POST['farm_id'] ?? 0);
        $rail = trim((string) ($_POST['payment_rail'] ?? ''));
        $farm = $farmId > 0 ? $this->farms->findByIdForOwner($farmId, (int) $user['id']) : null;

        if ($farm === null) {
            flash('billing_error', 'farm_required');
            redirect('/billing?lang=' . $this->t->locale());
        }

        $result = $this->subscriptions->startCheckout($user, $farm, $rail);
        if (!$result['ok']) {
            flash('billing_error', $result['error'] ?? 'gateway_error');
            redirect('/billing?lang=' . $this->t->locale());
        }

        redirect((string) $result['redirect_url']);
    }

    /** Paymob response (browser) callback and mock return land here. */
    public function callback(): string
    {
        $user = $this->auth->requireAuth();

        $success = ($_GET['success'] ?? '') === 'true' || ($_GET['status'] ?? '') === 'success';
        $reference = trim((string) ($_GET['order'] ?? $_GET['reference'] ?? ''));

        if ($success && $reference !== '') {
            $txn = $this->subscriptions->findByReference($reference);
            if ($txn !== null && (int) $txn['user_id'] === (int) $user['id']) {
                $this->subscriptions->activateTransaction((int) $txn['id'], $reference);
            }
        }

        return view('billing/result', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('billing.title'),
            'success' => $success,
        ]));
    }

    /** Paymob server-to-server webhook — no auth, HMAC-verified. */
    public function webhook(): never
    {
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        $hmac = (string) ($_GET['hmac'] ?? '');

        if (!is_array($payload) || !isset($payload['obj']) || !is_array($payload['obj'])) {
            http_response_code(400);
            exit('bad payload');
        }

        $obj = $payload['obj'];
        if (!$this->gateway->verifyPaymobHmac($obj, $hmac)) {
            http_response_code(403);
            exit('invalid hmac');
        }

        $success = ($obj['success'] ?? false) === true;
        $order = $obj['order'] ?? [];
        $reference = (string) (is_array($order) ? ($order['id'] ?? '') : '');

        if ($reference !== '') {
            $txn = $this->subscriptions->findByReference($reference);
            if ($txn !== null) {
                if ($success) {
                    $this->subscriptions->activateTransaction((int) $txn['id'], $reference);
                } else {
                    $this->subscriptions->failTransaction((int) $txn['id']);
                }
            }
        }

        http_response_code(200);
        exit('ok');
    }

    /** Sandbox gateway page (mock mode only). */
    public function mock(): string
    {
        $user = $this->auth->requireAuth();
        if (!$this->gateway->isMock()) {
            redirect('/billing?lang=' . $this->t->locale());
        }

        $txnId = (int) ($_GET['txn'] ?? 0);

        return view('billing/mock', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('billing.mock_title'),
            'txnId' => $txnId,
            'price' => $this->subscriptions->priceEgp(),
        ]));
    }

    public function mockComplete(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();
        if (!$this->gateway->isMock()) {
            redirect('/billing?lang=' . $this->t->locale());
        }

        $txnId = (int) ($_POST['txn_id'] ?? 0);
        $decision = trim((string) ($_POST['decision'] ?? ''));

        if ($decision === 'approve') {
            $this->subscriptions->activateTransaction($txnId, 'MOCK-APPROVED-' . $txnId);
            redirect('/billing/callback?status=success&lang=' . $this->t->locale());
        }

        $this->subscriptions->failTransaction($txnId);
        redirect('/billing/callback?status=failed&lang=' . $this->t->locale());
    }
}
