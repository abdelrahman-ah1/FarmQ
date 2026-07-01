<?php

declare(strict_types=1);

namespace FarmQ\Repositories;

use FarmQ\App;
use PDO;

final class BillingTransactionRepository
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
                "CREATE TABLE IF NOT EXISTS billing_transactions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    farm_id INTEGER NULL,
                    amount_egp REAL NOT NULL,
                    payment_rail TEXT NOT NULL CHECK(payment_rail IN ('fawry', 'vodafone_cash', 'meeza', 'instapay', 'card')),
                    gateway_reference TEXT NULL,
                    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'paid', 'failed', 'refunded')),
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (farm_id) REFERENCES farms(id)
                )"
            );
        } else {
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS billing_transactions (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id BIGINT UNSIGNED NOT NULL,
                    farm_id BIGINT UNSIGNED NULL,
                    amount_egp DECIMAL(10,2) NOT NULL,
                    payment_rail ENUM('fawry', 'vodafone_cash', 'meeza', 'instapay', 'card') NOT NULL,
                    gateway_reference VARCHAR(255) NULL,
                    status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (farm_id) REFERENCES farms(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }
    }

    public function createPending(int $userId, int $farmId, float $amount, string $rail): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO billing_transactions (user_id, farm_id, amount_egp, payment_rail, status)
             VALUES (:user_id, :farm_id, :amount, :rail, :status)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'farm_id' => $farmId,
            'amount' => $amount,
            'rail' => $rail,
            'status' => 'pending',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM billing_transactions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByReference(string $reference): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM billing_transactions WHERE gateway_reference = :ref LIMIT 1'
        );
        $stmt->execute(['ref' => $reference]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function setReference(int $id, string $reference): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE billing_transactions SET gateway_reference = :ref WHERE id = :id'
        );
        $stmt->execute(['ref' => $reference, 'id' => $id]);
    }

    public function markStatus(int $id, string $status, ?string $reference = null): void
    {
        if ($reference !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE billing_transactions SET status = :status, gateway_reference = :ref WHERE id = :id'
            );
            $stmt->execute(['status' => $status, 'ref' => $reference, 'id' => $id]);

            return;
        }

        $stmt = $this->pdo->prepare('UPDATE billing_transactions SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function recentForUser(int $userId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM billing_transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }
}
