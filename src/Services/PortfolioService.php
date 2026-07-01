<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\GeospatialJobRepository;
use FarmQ\Repositories\SoilSampleRepository;

final class PortfolioService
{
    private const LOW_NDVI = 0.35;

    public function __construct(
        private SoilSampleRepository $samples = new SoilSampleRepository(),
        private GeospatialJobRepository $geo = new GeospatialJobRepository(),
        private BlueprintService $blueprints = new BlueprintService(),
        private FarmAccessService $access = new FarmAccessService()
    ) {
    }

    /** @param array<int, array<string, mixed>> $farms */
    /** @return array<int, array<string, mixed>> */
    public function buildSummaries(array $farms, int $userId): array
    {
        $out = [];
        foreach ($farms as $farm) {
            $farmId = (int) $farm['id'];
            $sample = $this->samples->latestForFarm($farmId);
            $ndvi = $this->geo->latestCompletedByType($farmId, 'ndvi_process');
            $planRow = $this->blueprints->latestPlan($farmId);
            $ndviMean = $ndvi['result']['ndvi_mean'] ?? null;
            $isStale = $this->blueprints->isStale($farm, $planRow);
            $isLowNdvi = $ndviMean !== null && (float) $ndviMean < self::LOW_NDVI;

            $out[] = [
                'farm' => $farm,
                'latest_n' => $sample['npk_n'] ?? null,
                'latest_sample_date' => $sample['sample_date'] ?? null,
                'ndvi_mean' => $ndviMean,
                'crop' => $farm['selected_crop_code'] ?? null,
                'is_stale' => $isStale,
                'is_low_ndvi' => $isLowNdvi,
                'access_role' => $this->access->role($farmId, $userId),
                'has_plan' => $planRow !== null,
            ];
        }

        return $out;
    }

    /** @param array<int, array<string, mixed>> $summaries @param array<string, string> $filters */
    public function filter(array $summaries, array $filters): array
    {
        $region = $filters['region'] ?? '';
        $crop = $filters['crop'] ?? '';
        $flag = $filters['flag'] ?? '';

        return array_values(array_filter($summaries, static function (array $item) use ($region, $crop, $flag): bool {
            $farm = $item['farm'];
            if ($region !== '' && ($farm['region'] ?? '') !== $region) {
                return false;
            }
            if ($crop !== '' && ($item['crop'] ?? '') !== $crop) {
                return false;
            }
            if ($flag === 'stale' && empty($item['is_stale'])) {
                return false;
            }
            if ($flag === 'low_ndvi' && empty($item['is_low_ndvi'])) {
                return false;
            }

            return true;
        }));
    }

    /** @return array<int, string> */
    public function cropOptions(array $summaries): array
    {
        $crops = [];
        foreach ($summaries as $item) {
            $code = $item['crop'] ?? null;
            if ($code !== null && $code !== '') {
                $crops[(string) $code] = (string) $code;
            }
        }
        sort($crops);

        return $crops;
    }

    /** @return array{users: int, farms: int} */
    public function systemStats(): array
    {
        $pdo = \FarmQ\App::db()->pdo();

        return [
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'farms' => (int) $pdo->query('SELECT COUNT(*) FROM farms')->fetchColumn(),
        ];
    }
}
