<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\BillingTransactionRepository;
use FarmQ\Repositories\FarmRepository;

final class SubscriptionService
{
    public const RAILS = ['fawry', 'vodafone_cash', 'meeza', 'instapay', 'card'];

    public function __construct(
        private BillingTransactionRepository $transactions = new BillingTransactionRepository(),
        private FarmRepository $farms = new FarmRepository(),
        private PaymentGatewayService $gateway = new PaymentGatewayService()
    ) {
    }

    public function priceEgp(): float
    {
        return (float) (env('SUBSCRIPTION_PRICE_EGP', '450') ?? 450);
    }

    public function seasonDays(): int
    {
        return (int) (env('SUBSCRIPTION_DAYS', '180') ?? 180);
    }

    /**
     * Create a pending transaction and hand back a redirect to the gateway.
     *
     * @param array<string, mixed> $user
     * @param array<string, mixed> $farm
     * @return array{ok: bool, redirect_url?: string, error?: string}
     */
    public function startCheckout(array $user, array $farm, string $rail): array
    {
        if (!in_array($rail, self::RAILS, true)) {
            return ['ok' => false, 'error' => 'invalid_rail'];
        }

        $amount = $this->priceEgp();
        $txnId = $this->transactions->createPending(
            (int) $user['id'],
            (int) $farm['id'],
            $amount,
            $rail
        );

        $checkout = $this->gateway->createCheckout($txnId, $user, $farm, $amount, $rail);
        if (!$checkout['ok']) {
            $this->transactions->markStatus($txnId, 'failed');

            return ['ok' => false, 'error' => $checkout['error'] ?? 'gateway_error'];
        }

        if (!empty($checkout['reference'])) {
            $this->transactions->setReference($txnId, (string) $checkout['reference']);
        }

        return ['ok' => true, 'redirect_url' => (string) $checkout['redirect_url']];
    }

    /**
     * Activate the paid tier for a transaction (idempotent).
     *
     * @return array{ok: bool, farm_id?: int, error?: string}
     */
    public function activateTransaction(int $transactionId, ?string $reference = null): array
    {
        $txn = $this->transactions->find($transactionId);
        if ($txn === null) {
            return ['ok' => false, 'error' => 'not_found'];
        }

        if (($txn['status'] ?? '') === 'paid') {
            return ['ok' => true, 'farm_id' => (int) $txn['farm_id']];
        }

        $this->transactions->markStatus($transactionId, 'paid', $reference);
        $expiresAt = date('Y-m-d H:i:s', time() + $this->seasonDays() * 86400);
        $this->farms->activateTier((int) $txn['farm_id'], $expiresAt);

        return ['ok' => true, 'farm_id' => (int) $txn['farm_id']];
    }

    public function failTransaction(int $transactionId): void
    {
        $txn = $this->transactions->find($transactionId);
        if ($txn !== null && ($txn['status'] ?? '') === 'pending') {
            $this->transactions->markStatus($transactionId, 'failed');
        }
    }

    /** @return array<string, mixed>|null */
    public function findByReference(string $reference): ?array
    {
        return $this->transactions->findByReference($reference);
    }

    /** @return array<int, array<string, mixed>> */
    public function history(int $userId): array
    {
        return $this->transactions->recentForUser($userId);
    }
}
