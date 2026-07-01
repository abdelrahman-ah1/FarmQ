<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\FarmAccessRepository;
use FarmQ\Repositories\FarmRepository;

final class FarmContext
{
    public function __construct(
        private FarmRepository $farms = new FarmRepository(),
        private FarmAccessRepository $access = new FarmAccessRepository()
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(array $user): array
    {
        return $this->farms->findAllForUser((int) $user['id']);
    }

    public function active(array $user): ?array
    {
        $farms = $this->listForUser($user);
        if ($farms === []) {
            return null;
        }

        $activeId = $_SESSION['active_farm_id'] ?? null;
        if ($activeId !== null) {
            foreach ($farms as $farm) {
                if ((int) $farm['id'] === (int) $activeId) {
                    return $farm;
                }
            }
        }

        $_SESSION['active_farm_id'] = (int) $farms[0]['id'];

        return $farms[0];
    }

    public function switch(int $farmId, array $user): bool
    {
        $farm = $this->farms->findByIdForUser($farmId, (int) $user['id']);
        if ($farm === null) {
            return false;
        }

        $_SESSION['active_farm_id'] = $farmId;

        return true;
    }

    public function grantConsultantAccess(int $farmId, int $consultantUserId): bool
    {
        if ($this->farms->findById($farmId) === null) {
            return false;
        }

        return $this->access->grant($farmId, $consultantUserId);
    }
}
