<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class AlertService
{
    private const SEVERITY_RANK = ['high' => 0, 'moderate' => 1, 'low' => 2];

    /** Governorates in the Nile Valley / desert where radiative frost nights are more common. */
    private const FROST_PRONE_GOV = ['minya', 'asyut', 'sohag', 'qena', 'new_valley'];

    /** Coastal Delta governorates with chronic salinity pressure. */
    private const COASTAL_DELTA_GOV = ['kafr_el_sheikh', 'dakahlia', 'beheira'];

    /** @var array<string, array{frost: float, heat: float}> Crop-specific sensitivity thresholds (°C). */
    private const CROP_THRESHOLDS = [
        'citrus' => ['frost' => 4.0, 'heat' => 40.0],
        'grapes' => ['frost' => 3.0, 'heat' => 41.0],
        'strawberries' => ['frost' => 5.0, 'heat' => 36.0],
        'cotton' => ['frost' => 6.0, 'heat' => 40.0],
        'rice' => ['frost' => 8.0, 'heat' => 41.0],
        'maize' => ['frost' => 4.0, 'heat' => 38.0],
        'wheat' => ['frost' => 0.0, 'heat' => 34.0],
        'sugarcane' => ['frost' => 2.0, 'heat' => 43.0],
    ];

    /**
     * Egypt-relevant pest & disease pressure model, keyed by crop.
     * Each rule fires when the daily high sits in [min,max] and the moisture
     * condition matches (wet = rain >= 1mm, dry = rain < 0.5mm, any = ignore).
     *
     * @var array<string, array<int, array{code: string, kind: string, min: float, max: float, moisture: string}>>
     */
    private const PEST_MATRIX = [
        'cotton' => [
            ['code' => 'cotton_leafworm', 'kind' => 'pest', 'min' => 24.0, 'max' => 38.0, 'moisture' => 'any'],
            ['code' => 'pink_bollworm', 'kind' => 'pest', 'min' => 30.0, 'max' => 42.0, 'moisture' => 'dry'],
            ['code' => 'whitefly', 'kind' => 'pest', 'min' => 32.0, 'max' => 44.0, 'moisture' => 'dry'],
        ],
        'rice' => [
            ['code' => 'rice_stem_borer', 'kind' => 'pest', 'min' => 27.0, 'max' => 37.0, 'moisture' => 'any'],
            ['code' => 'rice_blast', 'kind' => 'disease', 'min' => 22.0, 'max' => 32.0, 'moisture' => 'wet'],
        ],
        'maize' => [
            ['code' => 'fall_armyworm', 'kind' => 'pest', 'min' => 26.0, 'max' => 38.0, 'moisture' => 'any'],
            ['code' => 'corn_borer', 'kind' => 'pest', 'min' => 28.0, 'max' => 40.0, 'moisture' => 'dry'],
        ],
        'wheat' => [
            ['code' => 'wheat_aphid', 'kind' => 'pest', 'min' => 14.0, 'max' => 28.0, 'moisture' => 'any'],
            ['code' => 'wheat_rust', 'kind' => 'disease', 'min' => 10.0, 'max' => 24.0, 'moisture' => 'wet'],
        ],
        'sugarcane' => [
            ['code' => 'sugarcane_borer', 'kind' => 'pest', 'min' => 27.0, 'max' => 40.0, 'moisture' => 'any'],
        ],
        'citrus' => [
            ['code' => 'citrus_leafminer', 'kind' => 'pest', 'min' => 24.0, 'max' => 36.0, 'moisture' => 'any'],
            ['code' => 'citrus_whitefly', 'kind' => 'pest', 'min' => 28.0, 'max' => 40.0, 'moisture' => 'dry'],
        ],
        'grapes' => [
            ['code' => 'powdery_mildew', 'kind' => 'disease', 'min' => 20.0, 'max' => 31.0, 'moisture' => 'any'],
            ['code' => 'grape_thrips', 'kind' => 'pest', 'min' => 26.0, 'max' => 38.0, 'moisture' => 'dry'],
        ],
        'strawberries' => [
            ['code' => 'spider_mite', 'kind' => 'pest', 'min' => 29.0, 'max' => 40.0, 'moisture' => 'dry'],
            ['code' => 'strawberry_botrytis', 'kind' => 'disease', 'min' => 14.0, 'max' => 25.0, 'moisture' => 'wet'],
        ],
    ];

    /**
     * Evaluate weather + soil + irrigation signals into ranked, actionable alerts.
     *
     * @param array<int, array<string, mixed>> $forecastDays
     * @param array<string, mixed>|null $latestSample
     * @param array<string, mixed> $context {crop_code, region, governorate, schedule}
     * @return array<int, array{type: string, severity: string, category: string, action: string, date?: string}>
     */
    public function evaluate(array $forecastDays, ?array $latestSample, string|array $context = ''): array
    {
        // Backwards compatible: allow a bare crop code string.
        if (is_string($context)) {
            $context = ['crop_code' => $context];
        }

        $cropCode = (string) ($context['crop_code'] ?? '');
        $region = (string) ($context['region'] ?? '');
        $gov = (string) ($context['governorate'] ?? '');
        $schedule = is_array($context['schedule'] ?? null) ? $context['schedule'] : null;

        $thresholds = self::CROP_THRESHOLDS[$cropCode] ?? ['frost' => 3.0, 'heat' => 42.0];
        $frostThreshold = $thresholds['frost'];
        $heatThreshold = $thresholds['heat'];

        // Frost-prone inland governorates get a warmer trigger (colder radiative nights).
        if (in_array($gov, self::FROST_PRONE_GOV, true)) {
            $frostThreshold += 2.0;
        }

        $alerts = [];
        $dryHotDays = 0;
        /** @var array<string, array{code: string, kind: string, first_date: string, days: int}> $pestHits */
        $pestHits = [];

        foreach ($forecastDays as $day) {
            $date = (string) ($day['date'] ?? '');
            $min = (float) ($day['temp_min'] ?? 99);
            $max = (float) ($day['temp_max'] ?? 0);
            $rain = (float) ($day['rain_mm'] ?? 0);
            $et0 = (float) ($day['et0_mm'] ?? 0);

            if ($min <= $frostThreshold - 3.0) {
                $alerts[] = $this->make('frost', 'high', 'weather', 'frost', $date);
            } elseif ($min <= $frostThreshold) {
                $alerts[] = $this->make('frost', 'moderate', 'weather', 'frost', $date);
            }

            if ($max >= $heatThreshold + 3.0) {
                $alerts[] = $this->make('heatwave', 'high', 'weather', 'heatwave', $date);
            } elseif ($max >= $heatThreshold) {
                $alerts[] = $this->make('heatwave', 'moderate', 'weather', 'heatwave', $date);
            }

            $this->accumulatePests($cropCode, $max, $rain, $date, $pestHits);

            if ($max >= 32.0 && $rain < 0.5 && $et0 >= 5.0) {
                $dryHotDays++;
            }
        }

        // Emit one predicted pest/disease alert per species, escalating with persistence.
        foreach ($pestHits as $hit) {
            $severity = $hit['days'] >= 3 ? 'high' : 'moderate';
            $alerts[] = $this->makePest($hit['code'], $hit['kind'], $severity, $hit['first_date'], $hit['days']);
        }

        // Soil salinity signals.
        $ec = $latestSample['salinity_ec'] ?? null;
        if ($ec !== null) {
            $ecVal = (float) $ec;
            if ($ecVal > 6.0) {
                $alerts[] = $this->make('high_salinity', 'high', 'soil', 'high_salinity');
            } elseif ($ecVal > 4.0) {
                $alerts[] = $this->make('high_salinity', 'moderate', 'soil', 'high_salinity');
            }

            // Coastal Delta: elevated salinity + sustained high ET is a leaching-management conflict.
            $coastal = $region === 'delta' && in_array($gov, self::COASTAL_DELTA_GOV, true);
            if (($ecVal > 3.0 || $coastal) && $dryHotDays >= 3) {
                $alerts[] = $this->make('salinity_irrigation_conflict', 'high', 'soil', 'salinity_irrigation_conflict');
            }
        }

        // Irrigation gap: high evaporative demand with no scheduled watering.
        if ($schedule !== null && $dryHotDays >= 4) {
            $hasWater = false;
            foreach ($schedule['days'] ?? [] as $sd) {
                if ((float) ($sd['irrigation_mm'] ?? 0) > 0) {
                    $hasWater = true;
                    break;
                }
            }
            if (!$hasWater) {
                $alerts[] = $this->make('irrigation_gap', 'moderate', 'water', 'irrigation_gap');
            }
        }

        return $this->rank($this->dedupe($alerts));
    }

    /**
     * @return array{type: string, severity: string, category: string, action: string, date?: string}
     */
    private function make(string $type, string $severity, string $category, string $action, string $date = ''): array
    {
        $alert = [
            'type' => $type,
            'severity' => $severity,
            'category' => $category,
            'action' => $action,
        ];
        if ($date !== '') {
            $alert['date'] = $date;
        }

        return $alert;
    }

    /**
     * Record a pest/disease hit for a given day, tracking earliest date and persistence.
     *
     * @param array<string, array{code: string, kind: string, first_date: string, days: int}> $hits
     */
    private function accumulatePests(string $cropCode, float $max, float $rain, string $date, array &$hits): void
    {
        foreach (self::PEST_MATRIX[$cropCode] ?? [] as $rule) {
            if ($max < $rule['min'] || $max > $rule['max']) {
                continue;
            }
            $moistureOk = match ($rule['moisture']) {
                'wet' => $rain >= 1.0,
                'dry' => $rain < 0.5,
                default => true,
            };
            if (!$moistureOk) {
                continue;
            }

            $code = $rule['code'];
            if (!isset($hits[$code])) {
                $hits[$code] = ['code' => $code, 'kind' => $rule['kind'], 'first_date' => $date, 'days' => 0];
            }
            $hits[$code]['days']++;
            if ($date !== '' && ($hits[$code]['first_date'] === '' || $date < $hits[$code]['first_date'])) {
                $hits[$code]['first_date'] = $date;
            }
        }
    }

    /**
     * @return array{type: string, severity: string, category: string, action: string, pest: string, kind: string, days: int, date?: string}
     */
    private function makePest(string $code, string $kind, string $severity, string $date, int $days): array
    {
        $alert = [
            'type' => $code,
            'severity' => $severity,
            'category' => 'biotic',
            'action' => $code,
            'pest' => $code,
            'kind' => $kind,
            'days' => $days,
        ];
        if ($date !== '') {
            $alert['date'] = $date;
        }

        return $alert;
    }

    /** @param array<int, array<string, mixed>> $alerts @return array<int, array<string, mixed>> */
    private function dedupe(array $alerts): array
    {
        $seen = [];
        $out = [];
        foreach ($alerts as $alert) {
            $key = $alert['type'] . '|' . ($alert['date'] ?? '') . '|' . $alert['severity'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $alert;
        }

        return $out;
    }

    /** @param array<int, array<string, mixed>> $alerts @return array<int, array<string, mixed>> */
    private function rank(array $alerts): array
    {
        usort($alerts, static function (array $a, array $b): int {
            $sa = self::SEVERITY_RANK[$a['severity']] ?? 9;
            $sb = self::SEVERITY_RANK[$b['severity']] ?? 9;
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }

            return ($a['date'] ?? '') <=> ($b['date'] ?? '');
        });

        return array_slice($alerts, 0, 12);
    }

    /** @param array<int, array<string, mixed>> $alerts @return array{high: int, moderate: int, low: int} */
    public function counts(array $alerts): array
    {
        $counts = ['high' => 0, 'moderate' => 0, 'low' => 0];
        foreach ($alerts as $alert) {
            $sev = (string) ($alert['severity'] ?? 'low');
            if (isset($counts[$sev])) {
                $counts[$sev]++;
            }
        }

        return $counts;
    }
}
