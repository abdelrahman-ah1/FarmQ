<?php

declare(strict_types=1);

namespace FarmQ\Repositories;

use FarmQ\App;
use PDO;

final class IrrigationScheduleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = App::db()->pdo();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS irrigation_schedules (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    farm_id INTEGER NOT NULL,
                    crop_code TEXT NULL,
                    week_start TEXT NOT NULL,
                    schedule_json TEXT NOT NULL,
                    generated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (farm_id) REFERENCES farms(id)
                )'
            );
        } else {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS irrigation_schedules (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    farm_id BIGINT UNSIGNED NOT NULL,
                    crop_code VARCHAR(64) NULL,
                    week_start DATE NOT NULL,
                    schedule_json JSON NOT NULL,
                    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (farm_id) REFERENCES farms(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }
    }

    /** @param array<string, mixed> $schedule */
    public function create(int $farmId, ?string $cropCode, string $weekStart, array $schedule): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO irrigation_schedules (farm_id, crop_code, week_start, schedule_json)
             VALUES (:farm_id, :crop_code, :week_start, :schedule_json)'
        );
        $stmt->execute([
            'farm_id' => $farmId,
            'crop_code' => $cropCode,
            'week_start' => $weekStart,
            'schedule_json' => json_encode($schedule, JSON_UNESCAPED_UNICODE),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function latestForFarm(int $farmId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM irrigation_schedules WHERE farm_id = :farm_id ORDER BY generated_at DESC, id DESC LIMIT 1'
        );
        $stmt->execute(['farm_id' => $farmId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['schedule'] = json_decode((string) $row['schedule_json'], true) ?? [];

        return $row;
    }
}
