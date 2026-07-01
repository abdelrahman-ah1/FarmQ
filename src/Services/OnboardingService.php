<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class OnboardingService
{
    /**
     * @return array<string, array{done: bool, href: string}>
     */
    public function steps(
        bool $hasFarm,
        bool $hasSoil,
        bool $hasCrop,
        bool $hasBlueprint
    ): array {
        return [
            'farm' => ['done' => $hasFarm, 'href' => '/farms/create'],
            'soil' => ['done' => $hasSoil, 'href' => '/ingestion'],
            'crop' => ['done' => $hasCrop, 'href' => '/ingestion'],
            'blueprint' => ['done' => $hasBlueprint, 'href' => '/blueprint'],
        ];
    }

    /** @param array<string, array{done: bool, href: string}> $steps */
    public function isComplete(array $steps): bool
    {
        foreach ($steps as $step) {
            if (!$step['done']) {
                return false;
            }
        }

        return true;
    }
}
