<?php

declare(strict_types=1);

namespace FarmQ\Services;

/**
 * Paymob (accept.paymob.com) integration with a built-in sandbox/mock mode.
 *
 * When PAYMENT_GATEWAY_API_KEY is empty the service operates in mock mode:
 * checkout redirects to an internal fake gateway page so the whole flow is
 * testable without live credentials. Configure the Paymob keys and it switches
 * to the live hosted-iframe flow automatically.
 */
final class PaymentGatewayService
{
    private const PAYMOB_BASE = 'https://accept.paymob.com/api';

    public function isMock(): bool
    {
        return (string) env('PAYMENT_GATEWAY_API_KEY', '') === '';
    }

    public function gatewayName(): string
    {
        return (string) env('PAYMENT_GATEWAY', 'paymob');
    }

    /**
     * Build a hosted checkout for a pending transaction.
     *
     * @param array<string, mixed> $user
     * @param array<string, mixed> $farm
     * @return array{ok: bool, redirect_url?: string, reference?: string, error?: string}
     */
    public function createCheckout(int $transactionId, array $user, array $farm, float $amountEgp, string $rail): array
    {
        if ($this->isMock()) {
            $reference = 'MOCK-' . $transactionId . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
            $base = rtrim((string) env('APP_URL', ''), '/');
            $locale = $_SESSION['locale'] ?? env('DEFAULT_LOCALE', 'ar');

            return [
                'ok' => true,
                'reference' => $reference,
                'redirect_url' => $base . '/billing/mock?txn=' . $transactionId . '&lang=' . $locale,
            ];
        }

        return $this->createPaymobCheckout($transactionId, $user, $farm, $amountEgp, $rail);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $farm
     * @return array{ok: bool, redirect_url?: string, reference?: string, error?: string}
     */
    private function createPaymobCheckout(int $transactionId, array $user, array $farm, float $amountEgp, string $rail): array
    {
        $apiKey = (string) env('PAYMENT_GATEWAY_API_KEY', '');
        $integrationId = (string) env('PAYMOB_INTEGRATION_ID', '');
        $iframeId = (string) env('PAYMOB_IFRAME_ID', '');
        if ($integrationId === '' || $iframeId === '') {
            return ['ok' => false, 'error' => 'gateway_misconfigured'];
        }

        $amountCents = (int) round($amountEgp * 100);

        $auth = $this->post(self::PAYMOB_BASE . '/auth/tokens', ['api_key' => $apiKey]);
        $authToken = $auth['token'] ?? null;
        if (!is_string($authToken)) {
            return ['ok' => false, 'error' => 'gateway_auth_failed'];
        }

        $order = $this->post(self::PAYMOB_BASE . '/ecommerce/orders', [
            'auth_token' => $authToken,
            'delivery_needed' => false,
            'amount_cents' => $amountCents,
            'currency' => 'EGP',
            'merchant_order_id' => 'farmq-' . $transactionId . '-' . time(),
            'items' => [[
                'name' => 'FarmQ Paid tier — farm #' . (int) ($farm['id'] ?? 0),
                'amount_cents' => $amountCents,
                'quantity' => 1,
            ]],
        ]);
        $orderId = $order['id'] ?? null;
        if ($orderId === null) {
            return ['ok' => false, 'error' => 'gateway_order_failed'];
        }

        [$firstName, $lastName] = $this->splitName((string) ($user['full_name'] ?? 'FarmQ User'));
        $paymentKey = $this->post(self::PAYMOB_BASE . '/acceptance/payment_keys', [
            'auth_token' => $authToken,
            'amount_cents' => $amountCents,
            'expiration' => 3600,
            'order_id' => $orderId,
            'currency' => 'EGP',
            'integration_id' => (int) $integrationId,
            'billing_data' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => (string) ($user['email'] ?? 'user@farmq.app'),
                'phone_number' => (string) ($user['phone'] ?? '+200000000000'),
                'country' => 'EG',
                'city' => 'NA',
                'street' => 'NA',
                'building' => 'NA',
                'floor' => 'NA',
                'apartment' => 'NA',
            ],
        ]);
        $token = $paymentKey['token'] ?? null;
        if (!is_string($token)) {
            return ['ok' => false, 'error' => 'gateway_key_failed'];
        }

        return [
            'ok' => true,
            'reference' => (string) $orderId,
            'redirect_url' => self::PAYMOB_BASE . '/acceptance/iframes/' . $iframeId . '?payment_token=' . $token,
        ];
    }

    /**
     * Verify a Paymob transaction-processed webhook HMAC.
     *
     * @param array<string, mixed> $obj The `obj` node of the callback payload.
     */
    public function verifyPaymobHmac(array $obj, string $providedHmac): bool
    {
        $secret = (string) env('PAYMOB_HMAC_SECRET', '');
        if ($secret === '' || $providedHmac === '') {
            return false;
        }

        $order = $obj['order'] ?? [];
        $source = $obj['source_data'] ?? [];
        $concat =
            $this->flag($obj['amount_cents'] ?? '')
            . $this->flag($obj['created_at'] ?? '')
            . $this->flag($obj['currency'] ?? '')
            . $this->flag($obj['error_occured'] ?? '')
            . $this->flag($obj['has_parent_transaction'] ?? '')
            . $this->flag($obj['id'] ?? '')
            . $this->flag($obj['integration_id'] ?? '')
            . $this->flag($obj['is_3d_secure'] ?? '')
            . $this->flag($obj['is_auth'] ?? '')
            . $this->flag($obj['is_capture'] ?? '')
            . $this->flag($obj['is_refunded'] ?? '')
            . $this->flag($obj['is_standalone_payment'] ?? '')
            . $this->flag($obj['is_voided'] ?? '')
            . $this->flag(is_array($order) ? ($order['id'] ?? '') : '')
            . $this->flag($obj['owner'] ?? '')
            . $this->flag($obj['pending'] ?? '')
            . $this->flag(is_array($source) ? ($source['pan'] ?? '') : '')
            . $this->flag(is_array($source) ? ($source['sub_type'] ?? '') : '')
            . $this->flag(is_array($source) ? ($source['type'] ?? '') : '')
            . $this->flag($obj['success'] ?? '');

        $calculated = hash_hmac('sha512', $concat, $secret);

        return hash_equals($calculated, $providedHmac);
    }

    private function flag(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        $first = $parts[0] ?? 'FarmQ';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'User';

        return [$first, $last];
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function post(string $url, array $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!is_string($response)) {
            return [];
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : [];
    }
}
