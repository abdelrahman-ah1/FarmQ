<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\App;
use FarmQ\Repositories\GeospatialJobRepository;
use FarmQ\Repositories\SoilSampleRepository;

final class PortfolioService
{
    public function __construct(
        private SoilSampleRepository $samples = new SoilSampleRepository(),
        private GeospatialJobRepository $geo = new GeospatialJobRepository()
    ) {
    }

    /** @param array<int, array<string, mixed>> $farms */
    /** @return array<int, array<string, mixed>> */
    public function summarize(array $farms): array
    {
        $out = [];
        foreach ($farms as $farm) {
            $farmId = (int) $farm['id'];
            $sample = $this->samples->latestForFarm($farmId);
            $ndvi = $this->geo->latestCompletedByType($farmId, 'ndvi_process');

            $out[] = [
                'farm' => $farm,
                'latest_n' => $sample['npk_n'] ?? null,
                'latest_sample_date' => $sample['sample_date'] ?? null,
                'ndvi_mean' => $ndvi['result']['ndvi_mean'] ?? null,
                'crop' => $farm['selected_crop_code'] ?? null,
            ];
        }

        return $out;
    }

    /** @return array{users: int, farms: int} */
    public function systemStats(): array
    {
        $pdo = App::db()->pdo();

        return [
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'farms' => (int) $pdo->query('SELECT COUNT(*) FROM farms')->fetchColumn(),
        ];
    }
}
