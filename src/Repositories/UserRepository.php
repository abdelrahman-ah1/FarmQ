<?php

declare(strict_types=1);

namespace FarmQ\Repositories;

use FarmQ\App;
use PDO;

final class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = App::db()->pdo();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /** @param array{email: string, password_hash: string, full_name: string, role: string, locale: string} $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, full_name, role, locale) VALUES (:email, :password_hash, :full_name, :role, :locale)'
        );
        $stmt->execute([
            'email' => strtolower(trim($data['email'])),
            'password_hash' => $data['password_hash'],
            'full_name' => trim($data['full_name']),
            'role' => $data['role'],
            'locale' => $data['locale'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
