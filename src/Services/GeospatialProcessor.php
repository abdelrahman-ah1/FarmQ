<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\GeospatialJobRepository;

/**
 * Unified geospatial processor — STAC scene fetch, NDVI estimate, soil-based deficiency map.
 */
final class GeospatialProcessor
{
    public function __construct(
        private SentinelStacService $stac = new SentinelStacService(),
        private DeficiencyMapEngine $deficiency = new DeficiencyMapEngine(),
        private GeospatialJobRepository $jobs = new GeospatialJobRepository()
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function process(string $jobType, array $payload): array
    {
        $farmId = (int) ($payload['farm_id'] ?? 0);
        $region = (string) ($payload['region'] ?? 'delta');
        $center = $payload['center'] ?? GeospatialStubProcessor::centroidForRegion($region);
        $polygon = $payload['polygon'] ?? null;
        $soil = $payload['soil'] ?? [];

        return match ($jobType) {
            'sentinel_fetch' => $this->sentinelFetch($farmId, $center, $polygon),
            'ndvi_process' => $this->ndviProcess($farmId, $center, $polygon),
            'deficiency_map' => $this->deficiencyMap($farmId, $center, $polygon, $soil),
            default => ['error' => 'Unknown job type: ' . $jobType],
        };
    }

    /** @param array{lat: float, lng: float} $center */
    /** @return array<string, mixed> */
    private function sentinelFetch(int $farmId, array $center, mixed $polygon): array
    {
        $poly = is_array($polygon) ? $polygon : null;
        $scene = $this->stac->searchScene($center, $poly);

        if ($scene === null) {
            $scene = [
                'scene_id' => 'S2A_fallback_' . date('Ymd'),
                'scan_date' => date('Y-m-d'),
                'cloud_cover_pct' => 0.0,
                'source' => 'Sentinel-2 L2A (fallback — no clear scene)',
            ];
        }

        return [
            'farm_id' => $farmId,
            'scene_id' => $scene['scene_id'],
            'scan_date' => $scene['scan_date'],
            'cloud_cover_pct' => $scene['cloud_cover_pct'],
            'center' => $center,
            'source' => $scene['source'],
            'scene' => $scene,
        ];
    }

    /** @param array{lat: float, lng: float} $center */
    /** @return array<string, mixed> */
    private function ndviProcess(int $farmId, array $center, mixed $polygon): array
    {
        $prior = $this->jobs->latestCompletedByType($farmId, 'sentinel_fetch');
        $scene = $prior['result']['scene'] ?? $prior['result'] ?? null;

        if (!is_array($scene) || empty($scene['scene_id'])) {
            $poly = is_array($polygon) ? $polygon : null;
            $scene = $this->stac->searchScene($center, $poly) ?? [
                'scene_id' => 'local_' . $farmId,
                'scan_date' => date('Y-m-d'),
                'source' => 'Sentinel-2 L2A (local estimate)',
            ];
        }

        $indices = $this->stac->estimateIndices((string) $scene['scene_id'], $farmId);

        return [
            'farm_id' => $farmId,
            'ndvi_mean' => $indices['ndvi_mean'],
            'ndre_mean' => $indices['ndre_mean'],
            'health' => $indices['health'],
            'center' => $center,
            'methodology' => 'NDVI/NDRE zonal mean (STAC scene; full COG via Python worker)',
            'scan_date' => $scene['scan_date'] ?? date('Y-m-d'),
            'scene_id' => $scene['scene_id'],
            'data_source' => $scene['source'] ?? 'Sentinel-2',
        ];
    }

    /**
     * @param array{lat: float, lng: float} $center
     * @param array<string, mixed> $soil
     * @return array<string, mixed>
     */
    private function deficiencyMap(int $farmId, array $center, mixed $polygon, array $soil): array
    {
        $ndviJob = $this->jobs->latestCompletedByType($farmId, 'ndvi_process');
        $ndvi = isset($ndviJob['result']['ndvi_mean']) ? (float) $ndviJob['result']['ndvi_mean'] : null;
        $poly = is_array($polygon) ? $polygon : null;

        return [
            'farm_id' => $farmId,
            'center' => $center,
            'geojson' => $this->deficiency->build($center, $poly, $soil, $ndvi),
            'method' => 'soil_npk_thresholds_with_ndvi',
        ];
    }
}
