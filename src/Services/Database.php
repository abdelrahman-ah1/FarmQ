<?php

declare(strict_types=1);

namespace FarmQ\Services;

use PDO;

final class Database
{
    private function __construct(private PDO $pdo)
    {
    }

    public static function connect(
        string $host,
        int $port,
        string $name,
        string $user,
        string $pass
    ): self {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::migrate($pdo);
        } catch (\PDOException) {
            $sqlitePath = base_path('database/farmq.sqlite');
            $isNew = !is_file($sqlitePath);
            $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            if ($isNew) {
                self::initSqlite($pdo);
            }
            self::migrate($pdo);
        }

        return new self($pdo);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    private static function initSqlite(PDO $pdo): void
    {
        $schema = base_path('database/schema_sqlite.sql');
        if (!is_file($schema)) {
            return;
        }

        $pdo->exec((string) file_get_contents($schema));
    }

    private static function migrate(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        self::ensureColumn(
            $pdo,
            $driver,
            'farms',
            'selected_crop_code',
            $driver === 'sqlite' ? 'TEXT NULL' : 'VARCHAR(64) NULL'
        );
        self::ensureColumn(
            $pdo,
            $driver,
            'farms',
            'governorate',
            $driver === 'sqlite' ? 'TEXT NULL' : 'VARCHAR(64) NULL'
        );
        self::ensureColumn(
            $pdo,
            $driver,
            'crop_baselines',
            'micronutrient_notes',
            $driver === 'sqlite' ? 'TEXT NULL' : 'JSON NULL'
        );

        self::seedMicronutrientNotes($pdo);
    }

    private static function ensureColumn(
        PDO $pdo,
        string $driver,
        string $table,
        string $column,
        string $definition
    ): void {
        if (self::hasColumn($pdo, $driver, $table, $column)) {
            return;
        }

        $pdo->exec(sprintf('ALTER TABLE %s ADD COLUMN %s %s', $table, $column, $definition));
    }

    private static function hasColumn(PDO $pdo, string $driver, string $table, string $column): bool
    {
        if ($driver === 'sqlite') {
            $cols = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll();
            foreach ($cols as $col) {
                if (($col['name'] ?? '') === $column) {
                    return true;
                }
            }

            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private static function seedMicronutrientNotes(PDO $pdo): void
    {
        $notes = [
            'citrus' => '{"en":"Monitor Zn and Fe foliar sprays on sandy reclaimed soils — common ARC extension guidance for export citrus.","ar":"راقب الرش الورقي للزنك والحديد في التربة الرملية بالواحات — إرشادات ARC الشائعة للحمضيات."}',
            'grapes' => '{"en":"Grapes on desert sand: schedule Fe chelate if leaf chlorosis appears mid-season.","ar":"العنب على الرمال: جدول شيلات الحديد عند ظهور اصفار الأوراق منتصف الموسم."}',
            'strawberries' => '{"en":"Strawberries require frequent Zn/Fe monitoring under drip fertigation in reclaimed land.","ar":"الفراولة تحتاج متابعة Zn/Fe مع التسميد بالتنقيط في الأراضي المستصلحة."}',
        ];

        foreach ($notes as $code => $json) {
            $stmt = $pdo->prepare(
                'UPDATE crop_baselines SET micronutrient_notes = :notes
                 WHERE crop_code = :code AND (micronutrient_notes IS NULL OR micronutrient_notes = \'\')'
            );
            $stmt->execute(['notes' => $json, 'code' => $code]);
        }
    }
}
