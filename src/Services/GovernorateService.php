<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class GovernorateService
{
    /** @return array<string, array<int, string>> */
    public static function all(): array
    {
        $file = base_path('resources/data/egypt_governorates.json');
        if (!is_file($file)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : [];
    }

    /** @return array<int, string> */
    public static function forRegion(string $region): array
    {
        return self::all()[$region] ?? [];
    }

    public static function isValidForRegion(string $region, string $governorate): bool
    {
        return in_array($governorate, self::forRegion($region), true);
    }
}
