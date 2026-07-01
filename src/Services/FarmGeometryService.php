<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class FarmGeometryService
{
    private const MIN_AREA_HA = 0.05;
    private const MAX_AREA_HA = 5000.0;

    /** @return array{lat: float, lng: float} */
    public function centroidForFarm(array $farm): array
    {
        $polygon = $this->decodePolygon($farm['polygon_geojson'] ?? null);
        if ($polygon !== null) {
            return $this->centroidFromPolygon($polygon);
        }

        return GeospatialStubProcessor::centroidForRegion((string) ($farm['region'] ?? 'delta'));
    }

    /** @return array{ok: bool, error?: string, geojson?: array<string, mixed>, area_ha?: float} */
    public function validateAndNormalize(string $rawJson): array
    {
        $decoded = json_decode($rawJson, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'invalid_json'];
        }

        $geometry = $decoded;
        if (($decoded['type'] ?? '') === 'Feature') {
            $geometry = $decoded['geometry'] ?? null;
        }

        if (!is_array($geometry) || ($geometry['type'] ?? '') !== 'Polygon') {
            return ['ok' => false, 'error' => 'invalid_polygon'];
        }

        $coords = $geometry['coordinates'][0] ?? null;
        if (!is_array($coords) || count($coords) < 4) {
            return ['ok' => false, 'error' => 'too_few_points'];
        }

        $areaHa = $this->areaHectares($coords);
        if ($areaHa < self::MIN_AREA_HA) {
            return ['ok' => false, 'error' => 'area_too_small'];
        }
        if ($areaHa > self::MAX_AREA_HA) {
            return ['ok' => false, 'error' => 'area_too_large'];
        }

        $normalized = [
            'type' => 'Feature',
            'properties' => ['area_ha' => round($areaHa, 2)],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => $geometry['coordinates'],
            ],
        ];

        return ['ok' => true, 'geojson' => $normalized, 'area_ha' => $areaHa];
    }

    /** @return array<string, mixed>|null */
    public function decodePolygon(mixed $stored): ?array
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        if (is_string($stored)) {
            $decoded = json_decode($stored, true);
        } else {
            $decoded = $stored;
        }

        if (!is_array($decoded)) {
            return null;
        }

        if (($decoded['type'] ?? '') === 'Feature') {
            return $decoded['geometry'] ?? null;
        }

        if (($decoded['type'] ?? '') === 'Polygon') {
            return $decoded;
        }

        return null;
    }

    /** @param array<string, mixed> $polygon */
    /** @return array{lat: float, lng: float} */
    public function centroidFromPolygon(array $polygon): array
    {
        $ring = $polygon['coordinates'][0] ?? [];
        if ($ring === []) {
            return GeospatialStubProcessor::centroidForRegion('delta');
        }

        $sumLat = 0.0;
        $sumLng = 0.0;
        $count = 0;
        foreach ($ring as $point) {
            if (!is_array($point) || count($point) < 2) {
                continue;
            }
            $sumLng += (float) $point[0];
            $sumLat += (float) $point[1];
            $count++;
        }

        if ($count === 0) {
            return GeospatialStubProcessor::centroidForRegion('delta');
        }

        return ['lat' => $sumLat / $count, 'lng' => $sumLng / $count];
    }

    /** @param array<int, array<int, float>> $ring */
    private function areaHectares(array $ring): float
    {
        $n = count($ring);
        if ($n < 3) {
            return 0.0;
        }

        $area = 0.0;
        for ($i = 0; $i < $n - 1; $i++) {
            $lng1 = deg2rad((float) $ring[$i][0]);
            $lat1 = deg2rad((float) $ring[$i][1]);
            $lng2 = deg2rad((float) $ring[$i + 1][0]);
            $lat2 = deg2rad((float) $ring[$i + 1][1]);
            $area += ($lng2 - $lng1) * (2 + sin($lat1) + sin($lat2));
        }

        $area = abs($area * 6378137.0 * 6378137.0 / 2.0);

        return $area / 10000.0;
    }
}
