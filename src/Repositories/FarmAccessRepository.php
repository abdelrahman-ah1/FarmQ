<?php

declare(strict_types=1);

namespace FarmQ\Repositories;

use FarmQ\App;
use PDO;

final class FarmAccessRepository
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
                'CREATE TABLE IF NOT EXISTS farm_access (
                    farm_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    access_role TEXT NOT NULL DEFAULT "consultant",
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (farm_id, user_id),
                    FOREIGN KEY (farm_id) REFERENCES farms(id),
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )'
            );
        } else {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS farm_access (
                    farm_id BIGINT UNSIGNED NOT NULL,
                    user_id BIGINT UNSIGNED NOT NULL,
                    access_role ENUM("consultant") NOT NULL DEFAULT "consultant",
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (farm_id, user_id),
                    FOREIGN KEY (farm_id) REFERENCES farms(id),
                    FOREIGN KEY (user_id) REFERENCES users(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }
    }

    public function grant(int $farmId, int $userId, string $role = 'viewer'): bool
    {
        if (!in_array($role, ['viewer', 'editor', 'consultant'], true)) {
            $role = 'viewer';
        }

        if ($this->hasAccess($farmId, $userId)) {
            return $this->updateRole($farmId, $userId, $role);
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO farm_access (farm_id, user_id, access_role) VALUES (:farm_id, :user_id, :role)'
            );
            $stmt->execute(['farm_id' => $farmId, 'user_id' => $userId, 'role' => $role]);

            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    public function updateRole(int $farmId, int $userId, string $role): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE farm_access SET access_role = :role WHERE farm_id = :farm_id AND user_id = :user_id'
        );
        $stmt->execute(['role' => $role, 'farm_id' => $farmId, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function getRole(int $farmId, int $userId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT access_role FROM farm_access WHERE farm_id = :farm_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['farm_id' => $farmId, 'user_id' => $userId]);
        $role = $stmt->fetchColumn();

        return $role !== false ? (string) $role : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function farmsForConsultant(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.*, fa.access_role FROM farms f
             INNER JOIN farm_access fa ON fa.farm_id = f.id
             WHERE fa.user_id = :user_id
             ORDER BY f.name ASC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function hasAccess(int $farmId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM farm_access WHERE farm_id = :farm_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['farm_id' => $farmId, 'user_id' => $userId]);

        return (bool) $stmt->fetchColumn();
    }
}
