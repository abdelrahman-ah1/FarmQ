<?php

declare(strict_types=1);

namespace FarmQ\Repositories;

use FarmQ\App;
use PDO;

final class SoilSampleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = App::db()->pdo();
    }

    /** @param array{sample_date: string, npk_n: ?float, npk_p: ?float, npk_k: ?float, ph: ?float, salinity_ec: ?float, source_csv_filename: ?string} $data */
    public function create(int $farmId, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO soil_samples (farm_id, sample_date, npk_n, npk_p, npk_k, ph, salinity_ec, source_csv_filename)
             VALUES (:farm_id, :sample_date, :npk_n, :npk_p, :npk_k, :ph, :salinity_ec, :source_csv_filename)'
        );
        $stmt->execute([
            'farm_id' => $farmId,
            'sample_date' => $data['sample_date'],
            'npk_n' => $data['npk_n'],
            'npk_p' => $data['npk_p'],
            'npk_k' => $data['npk_k'],
            'ph' => $data['ph'],
            'salinity_ec' => $data['salinity_ec'],
            'source_csv_filename' => $data['source_csv_filename'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<int, array{sample_date: string, npk_n: ?float, npk_p: ?float, npk_k: ?float, ph: ?float, salinity_ec: ?float, source_csv_filename: ?string}> $rows */
    public function createBatch(int $farmId, array $rows, ?string $filename): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $row['source_csv_filename'] = $filename;
            $this->create($farmId, $row);
            $count++;
        }

        return $count;
    }

    public function latestForFarm(int $farmId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM soil_samples WHERE farm_id = :farm_id ORDER BY sample_date DESC, id DESC LIMIT 1'
        );
        $stmt->execute(['farm_id' => $farmId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listForFarm(int $farmId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM soil_samples WHERE farm_id = :farm_id ORDER BY sample_date DESC, id DESC LIMIT :limit'
        );
        $stmt->bindValue('farm_id', $farmId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countForFarm(int $farmId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM soil_samples WHERE farm_id = :farm_id');
        $stmt->execute(['farm_id' => $farmId]);

        return (int) $stmt->fetchColumn();
    }

    public function existsOnDate(int $farmId, string $sampleDate): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM soil_samples WHERE farm_id = :farm_id AND sample_date = :sample_date LIMIT 1'
        );
        $stmt->execute(['farm_id' => $farmId, 'sample_date' => $sampleDate]);

        return (bool) $stmt->fetchColumn();
    }
}
