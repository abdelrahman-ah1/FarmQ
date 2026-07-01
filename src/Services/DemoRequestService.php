<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class DemoRequestService
{
    /** @return array{ok: bool, error?: string} */
    public function submit(array $input): array
    {
        $name = trim($input['full_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $message = trim($input['message'] ?? '');

        if ($name === '') {
            return ['ok' => false, 'error' => 'name_required'];
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'email_invalid'];
        }

        $record = [
            'submitted_at' => date('c'),
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'locale' => $input['locale'] ?? 'ar',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        $dir = base_path('storage/demo_requests');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $dir . '/requests.jsonl';
        file_put_contents($file, json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);

        $notify = env('DEMO_NOTIFY_EMAIL', '');
        if ($notify !== '') {
            $subject = 'FarmQ demo request — ' . $name;
            $body = "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\n\n{$message}";
            @mail($notify, $subject, $body, 'From: noreply@farmq.local');
        }

        return ['ok' => true];
    }
}
