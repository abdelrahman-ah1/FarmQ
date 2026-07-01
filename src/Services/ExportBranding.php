<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Localization\Translator;

final class ExportBranding
{
    private const LOGO_REL = 'img/FarmQ_Logo.png';

    public static function logoPath(): string
    {
        return base_path('public/assets/' . self::LOGO_REL);
    }

    public static function logoUrl(): string
    {
        return asset(self::LOGO_REL);
    }

    public static function logoDataUri(): ?string
    {
        static $cached = false;
        if ($cached !== false) {
            return $cached === '' ? null : $cached;
        }

        $path = self::logoPath();
        if (!is_file($path)) {
            $cached = '';

            return null;
        }

        $cached = 'data:image/png;base64,' . base64_encode((string) file_get_contents($path));

        return $cached;
    }

    /**
     * @param array<string, mixed> $activeFarm
     * @return list<list<string|null>>
     */
    public static function csvPreamble(
        Translator $t,
        array $activeFarm,
        ?string $cropName,
        string $generatedAt,
        string $season
    ): array {
        $rows = [
            [$t->get('export.brand_name')],
            [$t->get('export.brand_by')],
            [$t->get('export.farm'), (string) $activeFarm['name']],
            [$t->get('export.region'), $t->get('regions.' . $activeFarm['region'])],
        ];

        if ($cropName !== null && $cropName !== '') {
            $rows[] = [$t->get('export.crop'), $cropName];
        }

        $rows[] = [$t->get('export.season'), $t->get('seasons.' . $season)];
        $rows[] = [$t->get('export.generated_at'), substr($generatedAt, 0, 10)];
        $rows[] = [$t->get('export.logo_url'), self::logoUrl()];
        $rows[] = [];

        return $rows;
    }

    /** @param resource $out */
    public static function writeCsvPreamble(
        $out,
        Translator $t,
        array $activeFarm,
        ?string $cropName,
        string $generatedAt,
        string $season
    ): void {
        fwrite($out, "\xEF\xBB\xBF");

        foreach (self::csvPreamble($t, $activeFarm, $cropName, $generatedAt, $season) as $row) {
            fputcsv($out, $row);
        }
    }
}
