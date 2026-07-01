<?php

declare(strict_types=1);

namespace FarmQ\Services;

final class CsvIngestionService
{
    /** @var array<string, string> */
    private const COLUMN_ALIASES = [
        'sample_date' => 'sample_date',
        'date' => 'sample_date',
        'sample date' => 'sample_date',
        'sampling_date' => 'sample_date',
        'تاريخ' => 'sample_date',
        'تاريخ_العينة' => 'sample_date',
        'تاريخ العينة' => 'sample_date',
        'n' => 'npk_n',
        'npk_n' => 'npk_n',
        'nitrogen' => 'npk_n',
        'n_mg_kg' => 'npk_n',
        'ن' => 'npk_n',
        'نيتروجين' => 'npk_n',
        'p' => 'npk_p',
        'npk_p' => 'npk_p',
        'phosphorus' => 'npk_p',
        'p_mg_kg' => 'npk_p',
        'ف' => 'npk_p',
        'فوسفور' => 'npk_p',
        'k' => 'npk_k',
        'npk_k' => 'npk_k',
        'potassium' => 'npk_k',
        'k_mg_kg' => 'npk_k',
        'ك' => 'npk_k',
        'بوتاسium' => 'npk_k',
        'بوتاسيوم' => 'npk_k',
        'ph' => 'ph',
        'الرقم_الهيدrogenي' => 'ph',
        'salinity_ec' => 'salinity_ec',
        'salinity' => 'salinity_ec',
        'ec' => 'salinity_ec',
        'ec_dsm' => 'salinity_ec',
        'electrical_conductivity' => 'salinity_ec',
        'ملوحة' => 'salinity_ec',
        'الملوحة' => 'salinity_ec',
    ];

    /**
     * @return array{ok: bool, rows?: array<int, array<string, mixed>>, errors?: array<int, array<string, string>>, message?: string}
     */
    public function parse(string $filePath): array
    {
        if (!is_readable($filePath)) {
            return ['ok' => false, 'message' => 'unreadable'];
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return ['ok' => false, 'message' => 'unreadable'];
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false || $headerRow === [null]) {
            fclose($handle);

            return ['ok' => false, 'message' => 'empty'];
        }

        $map = $this->mapHeaders($headerRow);
        if (!isset($map['sample_date'])) {
            fclose($handle);

            return ['ok' => false, 'message' => 'missing_date_column'];
        }

        $rows = [];
        $errors = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if ($this->isEmptyRow($data)) {
                continue;
            }

            $parsed = $this->parseRow($data, $map, $line);
            if (isset($parsed['errors'])) {
                $errors[$line] = $parsed['errors'];
            } else {
                $rows[] = $parsed['row'];
            }
        }

        fclose($handle);

        if ($rows === []) {
            return [
                'ok' => false,
                'errors' => $errors,
                'message' => $errors === [] ? 'no_data_rows' : 'parse_failed',
            ];
        }

        return ['ok' => true, 'rows' => $rows, 'errors' => $errors];
    }

    /** @param array<int, string|null> $headerRow */
    /** @return array<string, int> */
    private function mapHeaders(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $index => $header) {
            $key = $this->normalizeHeader((string) $header);
            if (isset(self::COLUMN_ALIASES[$key])) {
                $map[self::COLUMN_ALIASES[$key]] = $index;
            }
        }

        return $map;
    }

    private function normalizeHeader(string $header): string
    {
        $key = trim($header);
        $key = preg_replace('/^\xEF\xBB\xBF/', '', $key) ?? $key;
        $key = mb_strtolower($key, 'UTF-8');
        $key = str_replace([' ', '-', '.'], '_', $key);

        return $key;
    }

    /** @param array<int, string|null> $data */
    /** @param array<string, int> $map */
    /** @return array{row?: array<string, mixed>, errors?: array<string, string>} */
    private function parseRow(array $data, array $map, int $line): array
    {
        $errors = [];

        $dateRaw = trim((string) ($data[$map['sample_date']] ?? ''));
        $sampleDate = $this->parseDate($dateRaw);
        if ($sampleDate === null) {
            $errors['sample_date'] = 'invalid_date';
        }

        $npkN = $this->optionalFloat($data, $map, 'npk_n', 0, 500, $errors, 'npk_n');
        $npkP = $this->optionalFloat($data, $map, 'npk_p', 0, 500, $errors, 'npk_p');
        $npkK = $this->optionalFloat($data, $map, 'npk_k', 0, 500, $errors, 'npk_k');
        $ph = $this->optionalFloat($data, $map, 'ph', 0, 14, $errors, 'ph');
        $salinity = $this->optionalFloat($data, $map, 'salinity_ec', 0, 50, $errors, 'salinity_ec');

        if ($npkN === null && $npkP === null && $npkK === null) {
            $errors['npk'] = 'missing_npk';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        return [
            'row' => [
                'sample_date' => $sampleDate,
                'npk_n' => $npkN,
                'npk_p' => $npkP,
                'npk_k' => $npkK,
                'ph' => $ph,
                'salinity_ec' => $salinity,
            ],
        ];
    }

    /** @param array<int, string|null> $data */
    /** @param array<string, int> $map */
    /** @param array<string, string> $errors */
    private function optionalFloat(
        array $data,
        array $map,
        string $field,
        float $min,
        float $max,
        array &$errors,
        string $errorKey
    ): ?float {
        if (!isset($map[$field])) {
            return null;
        }

        $raw = trim((string) ($data[$map[$field]] ?? ''));
        if ($raw === '') {
            return null;
        }

        $raw = str_replace(',', '.', $raw);
        if (!is_numeric($raw)) {
            $errors[$errorKey] = 'not_numeric';

            return null;
        }

        $value = (float) $raw;
        if ($value < $min || $value > $max) {
            $errors[$errorKey] = 'out_of_range';
        }

        return $value;
    }

    private function parseDate(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd.m.Y'];
        foreach ($formats as $format) {
            $dt = \DateTimeImmutable::createFromFormat($format, $raw);
            if ($dt !== false) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($raw);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /** @param array<int, string|null> $row */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
