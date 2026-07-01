<?php

declare(strict_types=1);

namespace FarmQ\Repositories;

use FarmQ\App;
use PDO;

final class GeospatialJobRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = App::db()->pdo();
    }

    /** @param array<string, mixed> $payload */
    public function create(int $farmId, string $jobType, array $payload = []): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO geospatial_jobs (farm_id, job_type, status, payload)
             VALUES (:farm_id, :job_type, :status, :payload)'
        );
        $stmt->execute([
            'farm_id' => $farmId,
            'job_type' => $jobType,
            'status' => 'queued',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $jobId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM geospatial_jobs WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $jobId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @param array<string, mixed> $result */
    public function markCompleted(int $jobId, array $result): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE geospatial_jobs SET status = :status, result_json = :result, completed_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute([
            'id' => $jobId,
            'status' => 'completed',
            'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function markFailed(int $jobId, string $error): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE geospatial_jobs SET status = :status, result_json = :result, completed_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute([
            'id' => $jobId,
            'status' => 'failed',
            'result' => json_encode(['error' => $error], JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function markProcessing(int $jobId): void
    {
        $stmt = $this->pdo->prepare('UPDATE geospatial_jobs SET status = :status WHERE id = :id');
        $stmt->execute(['id' => $jobId, 'status' => 'processing']);
    }

    /** @return array<int, array<string, mixed>> */
    public function latestBatchForFarm(int $farmId, int $limit = 3): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM geospatial_jobs WHERE farm_id = :farm_id ORDER BY created_at DESC, id DESC LIMIT :limit'
        );
        $stmt->bindValue('farm_id', $farmId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function latestCompletedByType(int $farmId, string $jobType): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM geospatial_jobs
             WHERE farm_id = :farm_id AND job_type = :job_type AND status = 'completed'
             ORDER BY completed_at DESC, id DESC LIMIT 1"
        );
        $stmt->execute(['farm_id' => $farmId, 'job_type' => $jobType]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['result'] = json_decode((string) ($row['result_json'] ?? ''), true) ?? [];

        return $row;
    }

    public function secondLatestCompletedByType(int $farmId, string $jobType): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM geospatial_jobs
             WHERE farm_id = :farm_id AND job_type = :job_type AND status = 'completed'
             ORDER BY completed_at DESC, id DESC LIMIT 1 OFFSET 1"
        );
        $stmt->execute(['farm_id' => $farmId, 'job_type' => $jobType]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['result'] = json_decode((string) ($row['result_json'] ?? ''), true) ?? [];

        return $row;
    }

    public function hasActiveJobs(int $farmId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM geospatial_jobs
             WHERE farm_id = :farm_id AND status IN ('queued', 'processing')"
        );
        $stmt->execute(['farm_id' => $farmId]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
