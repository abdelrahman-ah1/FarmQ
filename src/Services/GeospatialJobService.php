<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\GeospatialJobRepository;
use FarmQ\Repositories\SoilSampleRepository;

final class GeospatialJobService
{
    /** @var array<int, string> */
    private const PIPELINE = ['sentinel_fetch', 'ndvi_process', 'deficiency_map'];

    public function __construct(
        private GeospatialJobRepository $jobs = new GeospatialJobRepository(),
        private RedisQueueService $queue = new RedisQueueService(),
        private GeospatialProcessor $processor = new GeospatialProcessor(),
        private FarmGeometryService $geometry = new FarmGeometryService(),
        private SoilSampleRepository $samples = new SoilSampleRepository()
    ) {
    }

    /** @param array<string, mixed> $farm */
    public function startScan(array $farm): array
    {
        if ($this->jobs->hasActiveJobs((int) $farm['id'])) {
            return ['ok' => false, 'error' => 'scan_in_progress'];
        }

        $center = $this->geometry->centroidForFarm($farm);
        $sample = $this->samples->latestForFarm((int) $farm['id']);
        $polygon = null;
        if (!empty($farm['polygon_geojson'])) {
            $decoded = json_decode((string) $farm['polygon_geojson'], true);
            if (is_array($decoded)) {
                $polygon = $decoded;
            }
        }

        $payload = [
            'farm_id' => (int) $farm['id'],
            'region' => (string) $farm['region'],
            'center' => $center,
            'polygon' => $polygon,
            'soil' => [
                'npk_n' => $sample['npk_n'] ?? null,
                'npk_p' => $sample['npk_p'] ?? null,
                'npk_k' => $sample['npk_k'] ?? null,
                'ph' => $sample['ph'] ?? null,
                'salinity_ec' => $sample['salinity_ec'] ?? null,
            ],
            'crop_code' => $farm['selected_crop_code'] ?? null,
        ];

        $jobIds = [];
        foreach (self::PIPELINE as $jobType) {
            $jobId = $this->jobs->create((int) $farm['id'], $jobType, $payload);
            $jobIds[] = $jobId;

            $message = array_merge($payload, [
                'job_id' => $jobId,
                'job_type' => $jobType,
            ]);

            if (!$this->queue->push($message)) {
                $this->processInline($jobId, $jobType, $payload);
            }
        }

        return ['ok' => true, 'job_ids' => $jobIds];
    }

    /** @param array<string, mixed> $farm */
    /** @return array<string, mixed> */
    public function getMapData(array $farm): array
    {
        $farmId = (int) $farm['id'];
        $ndviJob = $this->jobs->latestCompletedByType($farmId, 'ndvi_process');
        $previousNdviJob = $this->jobs->secondLatestCompletedByType($farmId, 'ndvi_process');
        $deficiencyJob = $this->jobs->latestCompletedByType($farmId, 'deficiency_map');
        $sentinelJob = $this->jobs->latestCompletedByType($farmId, 'sentinel_fetch');

        $center = $this->geometry->centroidForFarm($farm);
        if ($ndviJob !== null && isset($ndviJob['result']['center'])) {
            $center = $ndviJob['result']['center'];
        }

        $farmPolygon = $this->geometry->decodePolygon($farm['polygon_geojson'] ?? null);
        $currentNdvi = isset($ndviJob['result']['ndvi_mean']) ? (float) $ndviJob['result']['ndvi_mean'] : null;
        $previousNdvi = isset($previousNdviJob['result']['ndvi_mean']) ? (float) $previousNdviJob['result']['ndvi_mean'] : null;
        $ndviDelta = ($currentNdvi !== null && $previousNdvi !== null)
            ? round($currentNdvi - $previousNdvi, 3)
            : null;

        $lastScanDate = $ndviJob['result']['scan_date'] ?? $ndviJob['completed_at'] ?? null;
        $rescanRecommended = false;
        if ($lastScanDate !== null) {
            $rescanRecommended = (time() - strtotime((string) $lastScanDate)) > 14 * 86400;
        }

        return [
            'center' => $center,
            'farm_polygon' => $farmPolygon,
            'scan_in_progress' => $this->jobs->hasActiveJobs($farmId),
            'ndvi' => $ndviJob['result'] ?? null,
            'previous_ndvi' => $previousNdvi,
            'ndvi_delta' => $ndviDelta,
            'deficiency' => $deficiencyJob['result']['geojson'] ?? null,
            'sentinel' => $sentinelJob['result'] ?? null,
            'has_data' => $ndviJob !== null,
            'last_scan_date' => $lastScanDate ? substr((string) $lastScanDate, 0, 10) : null,
            'rescan_recommended' => $rescanRecommended,
            'scan_history' => $this->buildScanHistory($farmId),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function buildScanHistory(int $farmId): array
    {
        $rows = [];
        $stmt = $this->jobs->latestBatchForFarm($farmId, 20);
        foreach ($stmt as $job) {
            if (($job['job_type'] ?? '') !== 'ndvi_process' || ($job['status'] ?? '') !== 'completed') {
                continue;
            }
            $result = json_decode((string) ($job['result_json'] ?? ''), true) ?? [];
            $rows[] = [
                'date' => substr((string) ($result['scan_date'] ?? $job['completed_at'] ?? ''), 0, 10),
                'ndvi_mean' => $result['ndvi_mean'] ?? null,
                'scene_id' => $result['scene_id'] ?? null,
            ];
        }

        return array_slice($rows, 0, 5);
    }

    /** @param array<string, mixed> $payload */
    private function processInline(int $jobId, string $jobType, array $payload): void
    {
        $this->jobs->markProcessing($jobId);
        try {
            $result = $this->processor->process($jobType, $payload);
            if (isset($result['error'])) {
                $this->jobs->markFailed($jobId, (string) $result['error']);
            } else {
                $this->jobs->markCompleted($jobId, $result);
            }
        } catch (\Throwable $e) {
            $this->jobs->markFailed($jobId, $e->getMessage());
        }
    }
}
