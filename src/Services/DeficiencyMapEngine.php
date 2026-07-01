<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class DeficiencyMapEngine
{
    /**
     * @param array{lat: float, lng: float} $center
     * @param array<string, mixed>|null $polygon
     * @param array<string, mixed> $soil
     */
    public function build(array $center, ?array $polygon, array $soil, ?float $ndvi = null): array
    {
        $lat = $center['lat'];
        $lng = $center['lng'];
        $d = 0.01;
        $features = [];

        $elements = [
            'n' => ['key' => 'npk_n', 'low' => 25.0, 'mid' => 45.0, 'offset' => [$d * 0.4, -$d * 0.5]],
            'p' => ['key' => 'npk_p', 'low' => 15.0, 'mid' => 30.0, 'offset' => [-$d * 0.3, $d * 0.2]],
            'k' => ['key' => 'npk_k', 'low' => 80.0, 'mid' => 120.0, 'offset' => [$d * 0.1, $d * 0.6]],
        ];

        foreach ($elements as $element => $cfg) {
            $value = isset($soil[$cfg['key']]) ? (float) $soil[$cfg['key']] : null;
            $severity = $this->severity($value, $cfg['low'], $cfg['mid']);
            if ($severity === null && $element === 'n' && $ndvi !== null && $ndvi < 0.45) {
                $severity = 'moderate';
            }
            if ($severity === null) {
                continue;
            }
            $features[] = $this->zone(
                $lat + $cfg['offset'][0],
                $lng + $cfg['offset'][1],
                $d * 0.55,
                $element,
                $severity
            );
        }

        if ($features === []) {
            $features[] = $this->zone($lat, $lng, $d * 0.4, 'n', 'low');
        }

        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    private function severity(?float $value, float $low, float $mid): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value < $low) {
            return 'high';
        }
        if ($value < $mid) {
            return 'moderate';
        }

        return null;
    }

    private function zone(float $lat, float $lng, float $size, string $element, string $severity): array
    {
        $s = $size / 2;

        return [
            'type' => 'Feature',
            'properties' => ['element' => $element, 'severity' => $severity],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [$lng - $s, $lat - $s],
                    [$lng + $s, $lat - $s],
                    [$lng + $s, $lat + $s],
                    [$lng - $s, $lat + $s],
                    [$lng - $s, $lat - $s],
                ]],
            ],
        ];
    }
}
