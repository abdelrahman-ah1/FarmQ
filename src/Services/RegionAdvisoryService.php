<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class RegionAdvisoryService
{
    /** @var array<string, array<int, string>> */
    private const COASTAL_DELTA_GOV = ['kafr_el_sheikh', 'dakahlia', 'beheira'];

    /**
     * @param array<string, mixed> $farm
     * @param array<string, mixed> $crop
     * @param array<string, float|null> $soil
     * @return array<int, string>
     */
    public function warningCodes(array $farm, array $crop, array $soil): array
    {
        $codes = [];
        $region = (string) ($farm['region'] ?? '');
        $gov = (string) ($farm['governorate'] ?? '');
        $cropCode = (string) ($crop['crop_code'] ?? '');

        if ($region === 'delta' && in_array($gov, self::COASTAL_DELTA_GOV, true)) {
            if (($soil['salinity_ec'] ?? null) === null || (float) $soil['salinity_ec'] >= 2.5) {
                $codes[] = 'delta_coastal_salinity';
            }
        }

        if ($region === 'delta' && $cropCode === 'rice' && ($soil['salinity_ec'] ?? 0) > 3.0) {
            $codes[] = 'rice_salinity';
        }

        if ($region === 'delta' && $cropCode === 'cotton') {
            $codes[] = 'cotton_delta_note';
        }

        if ($region === 'reclaimed_desert' && in_array($cropCode, ['citrus', 'grapes', 'strawberries'], true)) {
            $codes[] = 'desert_micronutrients';
        }

        return $codes;
    }

    /** @param array<string, mixed> $crop */
    public function micronutrientNote(array $crop, string $locale): ?string
    {
        $note = $crop['micronutrient_notes'] ?? null;
        if ($note === null || $note === '') {
            return null;
        }

        $decoded = json_decode((string) $note, true);
        if (!is_array($decoded)) {
            return (string) $note;
        }

        return (string) ($decoded[$locale] ?? $decoded['en'] ?? $decoded['ar'] ?? '');
    }
}
