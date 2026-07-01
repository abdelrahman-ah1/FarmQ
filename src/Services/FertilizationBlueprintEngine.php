<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class FertilizationBlueprintEngine
{
    private const SOIL_TO_KG_HA_FACTOR = 2.5;
    private const UREA_N_FRACTION = 0.46;
    private const DAP_P_FRACTION = 0.18;
    private const K2SO4_K_FRACTION = 0.50;

    /** @var array<string, float> */
    private const STAGE_SPLIT = [
        'basal' => 0.40,
        'growth' => 0.35,
        'reproductive' => 0.25,
    ];

    /**
     * @param array<string, mixed> $sample
     * @param array<string, mixed> $crop
     * @return array<string, mixed>
     */
    public function generate(array $sample, array $crop, array $farm = [], string $locale = 'en'): array
    {
        $targets = json_decode((string) $crop['npk_targets'], true);
        if (!is_array($targets)) {
            $targets = ['n' => 0, 'p' => 0, 'k' => 0];
        }

        $soil = [
            'n' => $this->floatOrNull($sample['npk_n'] ?? null),
            'p' => $this->floatOrNull($sample['npk_p'] ?? null),
            'k' => $this->floatOrNull($sample['npk_k'] ?? null),
            'ph' => $this->floatOrNull($sample['ph'] ?? null),
            'salinity_ec' => $this->floatOrNull($sample['salinity_ec'] ?? null),
        ];

        $targetN = (float) ($targets['n'] ?? 0);
        $targetP = (float) ($targets['p'] ?? 0);
        $targetK = (float) ($targets['k'] ?? 0);

        $deficits = [
            'n' => $this->deficit($soil['n'], $targetN),
            'p' => $this->deficit($soil['p'], $targetP),
            'k' => $this->deficit($soil['k'], $targetK),
        ];

        $elementsKgHa = [
            'n' => round($deficits['n'] * self::SOIL_TO_KG_HA_FACTOR, 1),
            'p' => round($deficits['p'] * self::SOIL_TO_KG_HA_FACTOR, 1),
            'k' => round($deficits['k'] * self::SOIL_TO_KG_HA_FACTOR, 1),
        ];

        $productsKgHa = [
            'urea' => $elementsKgHa['n'] > 0 ? round($elementsKgHa['n'] / self::UREA_N_FRACTION, 1) : 0.0,
            'dap' => $elementsKgHa['p'] > 0 ? round($elementsKgHa['p'] / self::DAP_P_FRACTION, 1) : 0.0,
            'potassium_sulfate' => $elementsKgHa['k'] > 0 ? round($elementsKgHa['k'] / self::K2SO4_K_FRACTION, 1) : 0.0,
        ];

        $schedule = $this->buildSchedule($productsKgHa);
        $advisory = new RegionAdvisoryService();
        $soilForAdvisory = $soil;
        $warnings = array_values(array_unique(array_merge(
            $this->buildWarnings($soil),
            $advisory->warningCodes($farm, $crop, $soilForAdvisory)
        )));

        return [
            'metadata' => [
                'generated_at' => date('c'),
                'season' => SeasonService::current(),
                'arc_reference' => $crop['arc_reference_note'] ?? '',
                'crop_code' => $crop['crop_code'],
                'tier_scope' => 'soil_only',
                'warnings' => $warnings,
                'micronutrient_note' => $advisory->micronutrientNote($crop, $locale),
            ],
            'soil' => $soil,
            'targets' => ['n' => $targetN, 'p' => $targetP, 'k' => $targetK],
            'deficits_mg_kg' => $deficits,
            'elements_kg_ha' => $elementsKgHa,
            'products_kg_ha' => $productsKgHa,
            'schedule' => $schedule,
        ];
    }

    /** @param array<string, float> $productsKgHa */
    /** @return array<int, array<string, mixed>> */
    private function buildSchedule(array $productsKgHa): array
    {
        $schedule = [];
        foreach (self::STAGE_SPLIT as $stage => $fraction) {
            $schedule[] = [
                'stage' => $stage,
                'urea' => round($productsKgHa['urea'] * $fraction, 1),
                'dap' => round($productsKgHa['dap'] * $fraction, 1),
                'potassium_sulfate' => round($productsKgHa['potassium_sulfate'] * $fraction, 1),
            ];
        }

        return $schedule;
    }

    /** @param array<string, float|null> $soil */
    /** @return array<int, string> */
    private function buildWarnings(array $soil): array
    {
        $warnings = [];

        if ($soil['ph'] !== null && $soil['ph'] < 6.0) {
            $warnings[] = 'low_ph';
        }
        if ($soil['ph'] !== null && $soil['ph'] > 8.5) {
            $warnings[] = 'high_ph';
        }
        if ($soil['salinity_ec'] !== null && $soil['salinity_ec'] > 4.0) {
            $warnings[] = 'high_salinity';
        }

        return $warnings;
    }

    private function deficit(?float $current, float $target): float
    {
        if ($current === null) {
            return $target;
        }

        return max(0.0, $target - $current);
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
