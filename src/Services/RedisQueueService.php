<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class RedisQueueService
{
    private const QUEUE_KEY = 'farmq:geospatial_jobs';

    public function push(array $payload): bool
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = (int) env('REDIS_PORT', '6379');
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        return $this->redisLpush($host, $port, self::QUEUE_KEY, $json);
    }

    private function redisLpush(string $host, int $port, string $key, string $value): bool
    {
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 2);
        if ($socket === false) {
            return false;
        }

        $command = "*3\r\n"
            . '$5' . "\r\nLPUSH\r\n"
            . '$' . strlen($key) . "\r\n{$key}\r\n"
            . '$' . strlen($value) . "\r\n{$value}\r\n";

        fwrite($socket, $command);
        fclose($socket);

        return true;
    }
}
