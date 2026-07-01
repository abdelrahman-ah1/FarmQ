<?php

declare(strict_types=1);

namespace FarmQ\Repositories;

use FarmQ\App;
use PDO;

final class CropRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = App::db()->pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function findByRegion(string $region): array
    {
        $stmt = $this->pdo->query('SELECT * FROM crop_baselines ORDER BY name_en ASC');
        $all = $stmt->fetchAll();
        $filtered = [];

        foreach ($all as $crop) {
            $tags = json_decode((string) $crop['region_tags'], true);
            if (is_array($tags) && in_array($region, $tags, true)) {
                $filtered[] = $crop;
            }
        }

        return $filtered;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM crop_baselines WHERE crop_code = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function isValidForRegion(string $cropCode, string $region): bool
    {
        $crop = $this->findByCode($cropCode);
        if ($crop === null) {
            return false;
        }

        $tags = json_decode((string) $crop['region_tags'], true);

        return is_array($tags) && in_array($region, $tags, true);
    }
}
