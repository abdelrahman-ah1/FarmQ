<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class SeasonService
{
    /** Shatawi (winter): Nov–Mar. Seifi (summer): Apr–Oct. */
    public static function fromDate(string $date): string
    {
        $month = (int) date('n', strtotime($date));

        return ($month >= 11 || $month <= 3) ? 'shatawi' : 'seifi';
    }

    public static function current(): string
    {
        return self::fromDate(date('Y-m-d'));
    }
}
