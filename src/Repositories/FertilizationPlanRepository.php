<?php

declare(strict_types=1);

namespace FarmQ\Repositories;

use FarmQ\App;
use PDO;

final class FertilizationPlanRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = App::db()->pdo();
    }

    /** @param array<string, mixed> $planData */
    public function create(
        int $farmId,
        string $cropCode,
        int $soilSampleId,
        array $planData,
        string $tierScope = 'soil_only'
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO fertilization_plans (farm_id, crop_code, soil_sample_id, plan_json, tier_scope)
             VALUES (:farm_id, :crop_code, :soil_sample_id, :plan_json, :tier_scope)'
        );
        $stmt->execute([
            'farm_id' => $farmId,
            'crop_code' => $cropCode,
            'soil_sample_id' => $soilSampleId,
            'plan_json' => json_encode($planData, JSON_UNESCAPED_UNICODE),
            'tier_scope' => $tierScope,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function latestForFarm(int $farmId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT fp.*, cb.name_en, cb.name_ar
             FROM fertilization_plans fp
             LEFT JOIN crop_baselines cb ON cb.crop_code = fp.crop_code
             WHERE fp.farm_id = :farm_id
             ORDER BY fp.generated_at DESC, fp.id DESC
             LIMIT 1'
        );
        $stmt->execute(['farm_id' => $farmId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['plan'] = json_decode((string) $row['plan_json'], true) ?? [];

        return $row;
    }

    /** @return array<int, array<string, mixed>> */
    public function historyForFarm(int $farmId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT fp.*, cb.name_en, cb.name_ar
             FROM fertilization_plans fp
             LEFT JOIN crop_baselines cb ON cb.crop_code = fp.crop_code
             WHERE fp.farm_id = :farm_id
             ORDER BY fp.generated_at DESC, fp.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('farm_id', $farmId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findByIdForFarm(int $planId, int $farmId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT fp.*, cb.name_en, cb.name_ar
             FROM fertilization_plans fp
             LEFT JOIN crop_baselines cb ON cb.crop_code = fp.crop_code
             WHERE fp.id = :id AND fp.farm_id = :farm_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $planId, 'farm_id' => $farmId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['plan'] = json_decode((string) $row['plan_json'], true) ?? [];

        return $row;
    }

    public function isStale(?array $plan, ?array $latestSample, ?string $selectedCropCode): bool
    {
        if ($plan === null) {
            return true;
        }

        if ($latestSample === null || $selectedCropCode === null) {
            return true;
        }

        return (int) $plan['soil_sample_id'] !== (int) $latestSample['id']
            || (string) $plan['crop_code'] !== $selectedCropCode;
    }
}
