<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class TierGate
{
    /** @var array<string, bool> */
    private const PAID_FEATURES = [
        'satellite' => true,
        'ndvi' => true,
        'deficiency_map' => true,
        'irrigation' => true,
        'alerts' => true,
        'forecast' => true,
        'multi_farm' => true,
    ];

    public function __construct(private array $farm)
    {
    }

    public function tier(): string
    {
        return $this->farm['tier'] ?? 'free';
    }

    public function isPaid(): bool
    {
        if (($this->farm['tier'] ?? 'free') !== 'paid') {
            return false;
        }

        $expires = $this->farm['tier_expires_at'] ?? null;
        if ($expires === null) {
            return true;
        }

        return strtotime((string) $expires) >= time();
    }

    public function can(string $feature): bool
    {
        if (env('DEV_UNLOCK_PAID', '0') === '1') {
            return true;
        }

        if (!isset(self::PAID_FEATURES[$feature])) {
            return true;
        }

        return $this->isPaid();
    }
}
