<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\IrrigationScheduleRepository;

final class IrrigationService
{
    /** @var array<string, float> */
    private const CROP_KC = [
        'cotton' => 1.15,
        'wheat' => 1.05,
        'rice' => 1.20,
        'maize' => 1.10,
        'sugarcane' => 1.25,
        'citrus' => 0.95,
        'grapes' => 0.90,
        'strawberries' => 0.85,
    ];

    public function __construct(
        private WeatherForecastService $weather = new WeatherForecastService(),
        private IrrigationScheduleRepository $schedules = new IrrigationScheduleRepository()
    ) {
    }

    /** @param array<string, mixed> $farm */
    public function generate(array $farm, ?string $cropCode): array
    {
        $forecast = $this->weather->fetchForRegion((string) $farm['region'], (int) $farm['id']);
        $days = $forecast['days'] ?? [];
        $kc = self::CROP_KC[$cropCode ?? ''] ?? 1.0;
        $region = (string) $farm['region'];

        $scheduleDays = [];
        foreach ($days as $day) {
            $et0 = (float) ($day['et0_mm'] ?? 0);
            $rain = (float) ($day['rain_mm'] ?? 0);
            $cropEt = round($et0 * $kc, 1);
            $effectiveRain = min($rain, $cropEt * 0.8);
            $irrigation = max(0.0, round($cropEt - $effectiveRain, 1));

            $scheduleDays[] = [
                'date' => $day['date'],
                'et0_mm' => $et0,
                'kc' => $kc,
                'crop_et_mm' => $cropEt,
                'rain_mm' => $rain,
                'irrigation_mm' => $irrigation,
            ];
        }

        $weekStart = $scheduleDays[0]['date'] ?? date('Y-m-d');
        $schedule = [
            'region' => $region,
            'crop_code' => $cropCode,
            'canal_rotation_note' => $region === 'upper_egypt'
                ? 'canal_rotation_upper_egypt'
                : null,
            'days' => $scheduleDays,
        ];

        $this->schedules->create((int) $farm['id'], $cropCode, $weekStart, $schedule);

        return $schedule;
    }

    public function latest(int $farmId): ?array
    {
        $row = $this->schedules->latestForFarm($farmId);

        return $row !== null ? $row['schedule'] : null;
    }
}
