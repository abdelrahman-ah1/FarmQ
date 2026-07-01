<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\CropRepository;
use FarmQ\Repositories\FarmRepository;

final class CropSelectionService
{
    public function __construct(
        private CropRepository $crops = new CropRepository(),
        private FarmRepository $farms = new FarmRepository()
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function availableForFarm(array $farm): array
    {
        return $this->crops->findByRegion((string) $farm['region']);
    }

    /** @return array{ok: bool, error?: string} */
    public function select(array $farm, string $cropCode): array
    {
        if (!$this->crops->isValidForRegion($cropCode, (string) $farm['region'])) {
            return ['ok' => false, 'error' => 'invalid_crop'];
        }

        $this->farms->updateCrop((int) $farm['id'], $cropCode);

        return ['ok' => true];
    }

    public function selectedCrop(array $farm, string $locale): ?array
    {
        $code = $farm['selected_crop_code'] ?? null;
        if ($code === null || $code === '') {
            return null;
        }

        $crop = $this->crops->findByCode($code);
        if ($crop === null) {
            return null;
        }

        $crop['display_name'] = $locale === 'ar' ? $crop['name_ar'] : $crop['name_en'];

        return $crop;
    }
}
