<?php

declare(strict_types=1);

namespace FarmQ\Repositories;

use FarmQ\App;
use PDO;

final class FarmRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = App::db()->pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllForUser(int $userId): array
    {
        $owned = $this->findByOwner($userId);
        $access = (new FarmAccessRepository())->farmsForConsultant($userId);
        $merged = [];
        foreach (array_merge($owned, $access) as $farm) {
            $merged[(int) $farm['id']] = $farm;
        }

        return array_values($merged);
    }

    public function findByIdForUser(int $farmId, int $userId): ?array
    {
        $farm = $this->findByIdForOwner($farmId, $userId);
        if ($farm !== null) {
            return $farm;
        }

        if ((new FarmAccessRepository())->hasAccess($farmId, $userId)) {
            return $this->findById($farmId);
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findByOwner(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM farms WHERE owner_user_id = :user_id ORDER BY created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM farms WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByIdForOwner(int $farmId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM farms WHERE id = :id AND owner_user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['id' => $farmId, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $ownerId, string $name, string $region, ?string $governorate = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO farms (owner_user_id, name, region, governorate, tier) VALUES (:owner_id, :name, :region, :governorate, :tier)'
        );
        $stmt->execute([
            'owner_id' => $ownerId,
            'name' => trim($name),
            'region' => $region,
            'governorate' => $governorate,
            'tier' => 'free',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updatePolygon(int $farmId, int $ownerId, array $geojson): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE farms SET polygon_geojson = :geojson WHERE id = :id AND owner_user_id = :owner_id'
        );
        $stmt->execute([
            'geojson' => json_encode($geojson, JSON_UNESCAPED_UNICODE),
            'id' => $farmId,
            'owner_id' => $ownerId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function updateGovernorate(int $farmId, int $ownerId, ?string $governorate): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE farms SET governorate = :governorate WHERE id = :id AND owner_user_id = :owner_id'
        );
        $stmt->execute([
            'governorate' => $governorate,
            'id' => $farmId,
            'owner_id' => $ownerId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function updateCrop(int $farmId, string $cropCode): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE farms SET selected_crop_code = :crop_code WHERE id = :id'
        );
        $stmt->execute(['crop_code' => $cropCode, 'id' => $farmId]);
    }
}
