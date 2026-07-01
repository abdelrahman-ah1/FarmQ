<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\App;
use FarmQ\Repositories\FertilizationPlanRepository;
use FarmQ\Repositories\GeospatialJobRepository;
use FarmQ\Repositories\SoilSampleRepository;
use PDO;

final class HistoricalService
{
    public function __construct(
        private SoilSampleRepository $samples = new SoilSampleRepository(),
        private FertilizationPlanRepository $plans = new FertilizationPlanRepository(),
        private GeospatialJobRepository $geo = new GeospatialJobRepository()
    ) {
    }

    /** @return array<string, mixed> */
    public function forFarm(int $farmId, bool $includePaid): array
    {
        $allSamples = $this->allSamples($farmId);
        $plans = $this->plans->historyForFarm($farmId, 20);
        $scans = $includePaid ? $this->geoScans($farmId) : [];

        return [
            'soil_samples' => $allSamples,
            'fertilization_plans' => $plans,
            'satellite_scans' => $scans,
            'season_summary' => $this->seasonSummary($allSamples),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function allSamples(int $farmId): array
    {
        $pdo = App::db()->pdo();
        $stmt = $pdo->prepare(
            'SELECT * FROM soil_samples WHERE farm_id = :farm_id ORDER BY sample_date ASC, id ASC'
        );
        $stmt->execute(['farm_id' => $farmId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    private function geoScans(int $farmId): array
    {
        $pdo = App::db()->pdo();
        $stmt = $pdo->prepare(
            "SELECT id, job_type, status, result_json, completed_at
             FROM geospatial_jobs
             WHERE farm_id = :farm_id AND job_type = 'ndvi_process' AND status = 'completed'
             ORDER BY completed_at DESC
             LIMIT 20"
        );
        $stmt->execute(['farm_id' => $farmId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['result'] = json_decode((string) ($row['result_json'] ?? ''), true) ?? [];
        }

        return $rows;
    }

    /** @param array<int, array<string, mixed>> $samples */
    /** @return array{shatawi: int, seifi: int} */
    private function seasonSummary(array $samples): array
    {
        $shatawi = 0;
        $seifi = 0;
        foreach ($samples as $sample) {
            $month = (int) date('n', strtotime((string) $sample['sample_date']));
            if ($month >= 11 || $month <= 3) {
                $shatawi++;
            } else {
                $seifi++;
            }
        }

        return ['shatawi' => $shatawi, 'seifi' => $seifi];
    }
}
