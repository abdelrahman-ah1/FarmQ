<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class GeospatialStubProcessor
{
    /** @var array<string, array{lat: float, lng: float}> */
    private const CENTROIDS = [
        'delta' => ['lat' => 30.90, 'lng' => 31.10],
        'upper_egypt' => ['lat' => 26.10, 'lng' => 32.65],
        'reclaimed_desert' => ['lat' => 30.30, 'lng' => 30.55],
    ];

    /** @param array<string, mixed> $payload */
    public function process(string $jobType, array $payload): array
    {
        $region = (string) ($payload['region'] ?? 'delta');
        $center = $payload['center'] ?? self::CENTROIDS[$region] ?? self::CENTROIDS['delta'];
        $farmId = (int) ($payload['farm_id'] ?? 0);

        return match ($jobType) {
            'sentinel_fetch' => [
                'farm_id' => $farmId,
                'scene_id' => 'S2A_stub_' . date('Ymd'),
                'scan_date' => date('Y-m-d'),
                'center' => $center,
                'cloud_cover_pct' => 4.2,
                'source' => 'Sentinel-2 L2A (stub)',
            ],
            'ndvi_process' => [
                'farm_id' => $farmId,
                'ndvi_mean' => 0.62,
                'ndre_mean' => 0.41,
                'health' => 'moderate',
                'center' => $center,
                'methodology' => 'NARSS-aligned NDVI/NDRE (stub)',
                'scan_date' => date('Y-m-d'),
            ],
            'deficiency_map' => [
                'farm_id' => $farmId,
                'center' => $center,
                'geojson' => $this->deficiencyGeoJson($center),
            ],
            default => ['error' => 'Unknown job type: ' . $jobType],
        };
    }

    /** @param array{lat: float, lng: float} $center */
    private function deficiencyGeoJson(array $center): array
    {
        $lat = $center['lat'];
        $lng = $center['lng'];
        $d = 0.012;

        return [
            'type' => 'FeatureCollection',
            'features' => [
                $this->zone($lat + $d * 0.5, $lng - $d, $d * 0.8, 'n', 'moderate'),
                $this->zone($lat - $d * 0.3, $lng + $d * 0.2, $d * 0.6, 'p', 'low'),
                $this->zone($lat + $d * 0.1, $lng + $d * 0.8, $d * 0.5, 'k', 'moderate'),
            ],
        ];
    }

    private function zone(float $lat, float $lng, float $size, string $element, string $severity): array
    {
        $s = $size / 2;

        return [
            'type' => 'Feature',
            'properties' => [
                'element' => $element,
                'severity' => $severity,
            ],
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

    /** @return array{lat: float, lng: float} */
    public static function centroidForRegion(string $region): array
    {
        return self::CENTROIDS[$region] ?? self::CENTROIDS['delta'];
    }
}
