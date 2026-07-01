<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Localization\Translator;

final class AlertDigestService
{
    /**
     * Persist a snapshot of the current high/moderate alerts for a farm and,
     * if an admin notify address is configured, dispatch a plain digest email.
     *
     * @param array<string, mixed> $farm
     * @param array<int, array<string, mixed>> $alerts
     */
    public function record(array $farm, array $alerts, Translator $t): void
    {
        $actionable = array_values(array_filter(
            $alerts,
            static fn (array $a): bool => in_array($a['severity'] ?? 'low', ['high', 'moderate'], true)
        ));

        if ($actionable === []) {
            return;
        }

        $farmId = (int) ($farm['id'] ?? 0);
        $dir = base_path('storage/alerts');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $lines = [];
        foreach ($actionable as $alert) {
            $label = $t->get('alerts.' . $alert['type'], ['date' => $alert['date'] ?? '']);
            $lines[] = sprintf('[%s] %s', strtoupper((string) $alert['severity']), $label);
        }

        $payload = [
            'farm_id' => $farmId,
            'farm_name' => (string) ($farm['name'] ?? ''),
            'generated_at' => date('c'),
            'counts' => (new AlertService())->counts($actionable),
            'alerts' => $actionable,
        ];

        $file = $dir . '/farm_' . $farmId . '.json';
        // Only rewrite when the alert fingerprint changes, to avoid notification spam.
        $fingerprint = md5(json_encode($lines, JSON_UNESCAPED_UNICODE) ?: '');
        $previous = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (is_array($previous) && ($previous['fingerprint'] ?? '') === $fingerprint) {
            return;
        }
        $payload['fingerprint'] = $fingerprint;

        @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->notify($farm, $lines);
    }

    /** @param array<int, string> $lines */
    private function notify(array $farm, array $lines): void
    {
        $to = env('ALERT_NOTIFY_EMAIL', '');
        if ($to === null || $to === '' || !function_exists('mail')) {
            return;
        }

        $subject = 'FarmQ alerts — ' . (string) ($farm['name'] ?? 'farm');
        $body = "New agronomic alerts for " . (string) ($farm['name'] ?? '') . ":\n\n" . implode("\n", $lines);
        @mail($to, $subject, $body, 'From: FarmQ <no-reply@farmq.app>');
    }
}
