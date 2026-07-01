<?php

declare(strict_types=1);

namespace FarmQ\Repositories;

use FarmQ\App;
use PDO;

final class FarmInviteRepository
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
                'CREATE TABLE IF NOT EXISTS farm_invites (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    farm_id INTEGER NOT NULL,
                    invite_code TEXT NOT NULL UNIQUE,
                    access_role TEXT NOT NULL DEFAULT "viewer",
                    created_by_user_id INTEGER NOT NULL,
                    expires_at TEXT NOT NULL,
                    used_at TEXT NULL,
                    used_by_user_id INTEGER NULL,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (farm_id) REFERENCES farms(id),
                    FOREIGN KEY (created_by_user_id) REFERENCES users(id)
                )'
            );
        } else {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS farm_invites (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    farm_id BIGINT UNSIGNED NOT NULL,
                    invite_code VARCHAR(32) NOT NULL,
                    access_role ENUM("viewer", "editor") NOT NULL DEFAULT "viewer",
                    created_by_user_id BIGINT UNSIGNED NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    used_at TIMESTAMP NULL,
                    used_by_user_id BIGINT UNSIGNED NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uk_farm_invites_code (invite_code),
                    KEY idx_farm_invites_farm (farm_id),
                    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
                    FOREIGN KEY (created_by_user_id) REFERENCES users(id),
                    FOREIGN KEY (used_by_user_id) REFERENCES users(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }
    }

    public function create(int $farmId, int $createdBy, string $role, int $ttlDays = 7): string
    {
        $code = bin2hex(random_bytes(8));
        $expires = date('Y-m-d H:i:s', time() + $ttlDays * 86400);
        $stmt = $this->pdo->prepare(
            'INSERT INTO farm_invites (farm_id, invite_code, access_role, created_by_user_id, expires_at)
             VALUES (:farm_id, :code, :role, :user_id, :expires)'
        );
        $stmt->execute([
            'farm_id' => $farmId,
            'code' => $code,
            'role' => $role,
            'user_id' => $createdBy,
            'expires' => $expires,
        ]);

        return $code;
    }

    public function findValidByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM farm_invites
             WHERE invite_code = :code AND used_at IS NULL AND expires_at > :now
             LIMIT 1'
        );
        $stmt->execute(['code' => $code, 'now' => date('Y-m-d H:i:s')]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markUsed(int $inviteId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE farm_invites SET used_at = :used_at, used_by_user_id = :user_id WHERE id = :id'
        );
        $stmt->execute([
            'used_at' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'id' => $inviteId,
        ]);
    }
}
