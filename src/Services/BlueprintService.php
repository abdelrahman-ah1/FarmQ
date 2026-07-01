<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\CropRepository;
use FarmQ\Repositories\FertilizationPlanRepository;
use FarmQ\Repositories\SoilSampleRepository;

final class BlueprintService
{
    public function __construct(
        private FertilizationBlueprintEngine $engine = new FertilizationBlueprintEngine(),
        private FertilizationPlanRepository $plans = new FertilizationPlanRepository(),
        private SoilSampleRepository $samples = new SoilSampleRepository(),
        private CropRepository $crops = new CropRepository()
    ) {
    }

    /** @return array{ready: bool, missing?: string} */
    public function readiness(array $farm): array
    {
        if (empty($farm['selected_crop_code'])) {
            return ['ready' => false, 'missing' => 'crop'];
        }

        $sample = $this->samples->latestForFarm((int) $farm['id']);
        if ($sample === null) {
            return ['ready' => false, 'missing' => 'soil'];
        }

        return ['ready' => true];
    }

    /**
     * @return array{ok: bool, error?: string, plan?: array<string, mixed>, planId?: int}
     */
    public function generateForFarm(array $farm, string $locale = 'en'): array
    {
        $readiness = $this->readiness($farm);
        if (!$readiness['ready']) {
            return ['ok' => false, 'error' => $readiness['missing'] ?? 'unknown'];
        }

        $sample = $this->samples->latestForFarm((int) $farm['id']);
        $crop = $this->crops->findByCode((string) $farm['selected_crop_code']);

        if ($sample === null || $crop === null) {
            return ['ok' => false, 'error' => 'invalid'];
        }

        if (!$this->crops->isValidForRegion((string) $crop['crop_code'], (string) $farm['region'])) {
            return ['ok' => false, 'error' => 'invalid_crop'];
        }

        $planData = $this->engine->generate($sample, $crop, $farm, $locale);
        $planId = $this->plans->create(
            (int) $farm['id'],
            (string) $crop['crop_code'],
            (int) $sample['id'],
            $planData
        );

        $saved = $this->plans->findByIdForFarm($planId, (int) $farm['id']);

        return ['ok' => true, 'plan' => $saved, 'planId' => $planId];
    }

    public function latestPlan(int $farmId): ?array
    {
        return $this->plans->latestForFarm($farmId);
    }

    public function isStale(array $farm, ?array $plan): bool
    {
        $sample = $this->samples->latestForFarm((int) $farm['id']);

        return $this->plans->isStale(
            $plan,
            $sample,
            $farm['selected_crop_code'] ?? null
        );
    }

    /** @param array<string, mixed> $planRow */
    public function cropDisplayName(array $planRow, string $locale): string
    {
        if ($locale === 'ar') {
            return (string) ($planRow['name_ar'] ?? $planRow['crop_code']);
        }

        return (string) ($planRow['name_en'] ?? $planRow['crop_code']);
    }
}
