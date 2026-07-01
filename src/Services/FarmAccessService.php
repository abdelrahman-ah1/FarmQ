<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\FarmAccessRepository;
use FarmQ\Repositories\FarmRepository;

final class FarmAccessService
{
    public function __construct(
        private FarmRepository $farms = new FarmRepository(),
        private FarmAccessRepository $access = new FarmAccessRepository()
    ) {
    }

    public function isOwner(int $farmId, int $userId): bool
    {
        $farm = $this->farms->findByIdForOwner($farmId, $userId);

        return $farm !== null;
    }

    public function canView(int $farmId, int $userId): bool
    {
        if ($this->isOwner($farmId, $userId)) {
            return true;
        }

        return $this->access->hasAccess($farmId, $userId);
    }

    public function canEdit(int $farmId, int $userId): bool
    {
        if ($this->isOwner($farmId, $userId)) {
            return true;
        }

        $role = $this->access->getRole($farmId, $userId);

        return $role === 'editor' || $role === 'consultant';
    }

    /** @return 'owner'|'viewer'|'editor'|null */
    public function role(int $farmId, int $userId): ?string
    {
        if ($this->isOwner($farmId, $userId)) {
            return 'owner';
        }

        $role = $this->access->getRole($farmId, $userId);
        if ($role === 'consultant') {
            return 'editor';
        }

        return $role;
    }
}
