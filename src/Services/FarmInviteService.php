<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\FarmAccessRepository;
use FarmQ\Repositories\FarmInviteRepository;
use FarmQ\Repositories\FarmRepository;

final class FarmInviteService
{
    public function __construct(
        private FarmInviteRepository $invites = new FarmInviteRepository(),
        private FarmRepository $farms = new FarmRepository(),
        private FarmAccessRepository $access = new FarmAccessRepository()
    ) {
    }

    /** @return array{ok: bool, error?: string, code?: string, url?: string} */
    public function createForFarm(int $farmId, int $ownerId, string $role): array
    {
        if ($this->farms->findByIdForOwner($farmId, $ownerId) === null) {
            return ['ok' => false, 'error' => 'not_owner'];
        }
        if (!in_array($role, ['viewer', 'editor'], true)) {
            return ['ok' => false, 'error' => 'invalid_role'];
        }

        $code = $this->invites->create($farmId, $ownerId, $role);
        $base = rtrim(env('APP_URL', ''), '/');
        $locale = $_SESSION['locale'] ?? env('DEFAULT_LOCALE', 'ar');

        return [
            'ok' => true,
            'code' => $code,
            'url' => $base . '/portfolio/accept?code=' . urlencode($code) . '&lang=' . $locale,
        ];
    }

    /** @return array{ok: bool, error?: string, farm_id?: int} */
    public function accept(string $code, int $consultantUserId): array
    {
        $invite = $this->invites->findValidByCode($code);
        if ($invite === null) {
            return ['ok' => false, 'error' => 'invalid_invite'];
        }

        $farmId = (int) $invite['farm_id'];
        $role = (string) $invite['access_role'];
        if (!$this->access->grant($farmId, $consultantUserId, $role)) {
            if (!$this->access->hasAccess($farmId, $consultantUserId)) {
                return ['ok' => false, 'error' => 'link_failed'];
            }
        }

        $this->invites->markUsed((int) $invite['id'], $consultantUserId);

        return ['ok' => true, 'farm_id' => $farmId];
    }
}
