<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class AlertService
{
    /**
     * @param array<int, array<string, mixed>> $forecastDays
     * @param array<string, mixed>|null $latestSample
     * @return array<int, array{type: string, severity: string, date?: string}>
     */
    public function evaluate(array $forecastDays, ?array $latestSample, string $cropCode = ''): array
    {
        $alerts = [];

        foreach ($forecastDays as $day) {
            $date = (string) ($day['date'] ?? '');
            $min = (float) ($day['temp_min'] ?? 99);
            $max = (float) ($day['temp_max'] ?? 0);
            $rain = (float) ($day['rain_mm'] ?? 0);

            if ($min <= 2.0) {
                $alerts[] = ['type' => 'frost', 'severity' => 'high', 'date' => $date];
            } elseif ($min <= 5.0) {
                $alerts[] = ['type' => 'frost', 'severity' => 'moderate', 'date' => $date];
            }

            if ($max >= 42.0) {
                $alerts[] = ['type' => 'heatwave', 'severity' => 'high', 'date' => $date];
            } elseif ($max >= 38.0) {
                $alerts[] = ['type' => 'heatwave', 'severity' => 'moderate', 'date' => $date];
            }

            if ($max >= 28.0 && $max <= 35.0 && $rain < 0.5 && in_array($cropCode, ['cotton', 'rice', 'maize'], true)) {
                $alerts[] = ['type' => 'pest_risk', 'severity' => 'moderate', 'date' => $date];
            }
        }

        if ($latestSample !== null) {
            $ec = $latestSample['salinity_ec'] ?? null;
            if ($ec !== null && (float) $ec > 4.0) {
                $alerts[] = ['type' => 'high_salinity', 'severity' => 'moderate'];
            }
        }

        return $this->dedupe($alerts);
    }

    /** @param array<int, array{type: string, severity: string, date?: string}> $alerts */
    /** @return array<int, array{type: string, severity: string, date?: string}> */
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

        return array_slice($out, 0, 8);
    }
}
