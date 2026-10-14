<?php

namespace App\Services;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SiptDatasetReader
{
    /**
     * Standard 26 column keys in order.
     */
    public const KOLOM_26 = [
        'no',
        'upt',
        'tanggal_pengambilan_iklan',
        'tgl_kirim_ka_upt_ke_pusat',
        'tanggal_evaluasi_pusat',
        'jenis_produk',
        'nama_produk',
        'nomor_izin_edar',
        'khasiat_indikasi',
        'pendaftar',
        'asal_produk',
        'kategori_media',
        'media_iklan',
        'tempat_pengambilan_iklan',
        'pencantuman_nomor_izin_edar',
        'link_url_iklan',
        'klaim_dalam_promosi_iklan',
        'klasifikasi_klaim',
        'pembuat_iklan',
        'hasil_penilaian_iklan_upt',
        'kesimpulan_upt',
        'kesimpulan_pusat',
        'kesesuaian_hasil_evaluasi',
        'kategori_tmk',
        'rincian_tmk',
        'tindak_lanjut',
    ];

    /**
     * Standard 26 column headers in order.
     */
    public const HEADER_26 = [
        'NO',
        'UPT',
        'TANGGAL PENGAMBILAN IKLAN',
        'TGL KIRIM KA UPT KE PUSAT',
        'TANGGAL EVALUASI PUSAT',
        'JENIS PRODUK',
        'NAMA PRODUK',
        'NOMOR IZIN EDAR',
        'KHASIAT/INDIKASI',
        'PENDAFTAR',
        'ASAL PRODUK',
        'KATEGORI MEDIA',
        'MEDIA IKLAN',
        'TEMPAT PENGAMBILAN IKLAN',
        'PENCANTUMAN NOMOR IZIN EDAR',
        'LINK URL IKLAN',
        'KLAIM DALAM PROMOSI/IKLAN',
        'KLASIFIKASI KLAIM',
        'PEMBUAT IKLAN',
        'HASIL PENILAIAN IKLAN UPT',
        'KESIMPULAN UPT',
        'KESIMPULAN PUSAT',
        'KESESUAIAN HASIL EVALUASI',
        'KATEGORI TMK',
        'RINCIAN TMK',
        'TINDAK LANJUT',
    ];

    /**
     * Read SIPT dataset from file path (supports HTML-based .xls/.html and real .xlsx).
     *
     * @return array{
     *     header: array{komoditi: ?string, periode_mulai: ?string, periode_akhir: ?string},
     *     columns: array<int, string>,
     *     rows: array<int, array<string, ?string>>
     * }
     */
    public function read(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new Exception("File {$filePath} tidak ditemukan.");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Detect if .xls is actually HTML
        $handle = fopen($filePath, 'r');
        $firstBytes = fread($handle, 1024);
        fclose($handle);

        $isHtml = (str_contains(strtolower($firstBytes), '<html') ||
                   str_contains(strtolower($firstBytes), '<table') ||
                   str_contains(strtolower($firstBytes), '<!doctype html') ||
                   $extension === 'html' ||
                   $extension === 'htm');

        if ($isHtml || $extension === 'xls') {
            try {
                return $this->readHtml($filePath);
            } catch (Exception $e) {
                // If HTML reading failed and it's xlsx, fallback to Spreadsheet
                if ($extension === 'xlsx') {
                    return $this->readSpreadsheet($filePath);
                }
                throw $e;
            }
        }

        return $this->readSpreadsheet($filePath);
    }

    /**
     * Read HTML-based SIPT file containing Table 1 (Header) and Table 2 (26 columns).
     */
    protected function readHtml(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("Gagal membaca isi file {$filePath}.");
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // Load with UTF-8 support
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $tables = $xpath->query('//table');

        if ($tables->length === 0) {
            throw new Exception("Format tabel tidak ditemukan di dalam file SIPT.");
        }

        $headerInfo = [
            'komoditi' => null,
            'periode_mulai' => null,
            'periode_akhir' => null,
        ];

        // Parse Table 1 (Header Metadata)
        if ($tables->length >= 2) {
            $table1 = $tables->item(0);
            $rows1 = $xpath->query('.//tr', $table1);
            foreach ($rows1 as $tr) {
                $text = trim(preg_replace('/\s+/', ' ', $tr->textContent));
                // Match KOMODITI
                if (preg_match('/KOMODITI\s*[:\t]?\s*([^\n\r]+)/i', $text, $matches)) {
                    $headerInfo['komoditi'] = trim($matches[1]);
                }
                // Match PERIODE MULAI
                if (preg_match('/PERIODE\s*MULAI\s*[:\t]?\s*([0-9]{1,2}\s+[a-zA-Z]+\s+[0-9]{4})/i', $text, $matches)) {
                    $headerInfo['periode_mulai'] = trim($matches[1]);
                }
                // Match PERIODE AKHIR
                if (preg_match('/PERIODE\s*AKHIR\s*[:\t]?\s*([0-9]{1,2}\s+[a-zA-Z]+\s+[0-9]{4})/i', $text, $matches)) {
                    $headerInfo['periode_akhir'] = trim($matches[1]);
                }
            }
        }

        // Parse Table 2 (Data Table with 26 columns)
        $dataTable = $tables->length >= 2 ? $tables->item(1) : $tables->item(0);
        $trNodes = $xpath->query('.//tr', $dataTable);

        $detectedColumns = [];
        $rows = [];
        $headerRowFound = false;

        foreach ($trNodes as $index => $tr) {
            $cells = $xpath->query('.//th | .//td', $tr);
            $cellValues = [];
            foreach ($cells as $cell) {
                $val = trim(preg_replace('/[\t\n\r]+/', ' ', $cell->textContent));
                $val = preg_replace('/\s+/', ' ', $val);
                $cellValues[] = $val;
            }

            // Check if this row is the header row
            if (! $headerRowFound) {
                $joined = strtoupper(implode(' ', $cellValues));
                if (str_contains($joined, 'UPT') && (str_contains($joined, 'JENIS PRODUK') || str_contains($joined, 'NOMOR IZIN EDAR'))) {
                    $detectedColumns = $cellValues;
                    $headerRowFound = true;
                    continue;
                }
            }

            if (! $headerRowFound) {
                continue;
            }

            // Empty row check
            $nonEmpty = array_filter($cellValues, fn ($v) => $v !== '' && $v !== null);
            if (empty($nonEmpty)) {
                continue;
            }

            // Map cells to standard 26 keys
            $mappedRow = [];
            foreach (self::KOLOM_26 as $keyIdx => $key) {
                $mappedRow[$key] = $cellValues[$keyIdx] ?? null;
            }

            $rows[] = $mappedRow;
        }

        return [
            'header' => $headerInfo,
            'columns' => $detectedColumns,
            'rows' => $rows,
        ];
    }

    /**
     * Read native Excel .xlsx file.
     */
    protected function readSpreadsheet(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $sheetData = $sheet->toArray(null, true, true, false);

        $headerInfo = [
            'komoditi' => null,
            'periode_mulai' => null,
            'periode_akhir' => null,
        ];

        $detectedColumns = [];
        $rows = [];
        $headerRowFound = false;

        foreach ($sheetData as $rowValues) {
            $rowValues = array_map(function ($val) {
                if ($val === null) return '';
                $val = trim((string)$val);
                return preg_replace('/\s+/', ' ', $val);
            }, $rowValues);

            // Check if header info exists in top rows
            if (! $headerRowFound) {
                $joined = strtoupper(implode(' ', $rowValues));
                if (preg_match('/KOMODITI\s*[:\t]?\s*([^\n\r]+)/i', $joined, $m)) {
                    $headerInfo['komoditi'] = trim($m[1]);
                }
                if (preg_match('/PERIODE\s*MULAI\s*[:\t]?\s*([0-9]{1,2}\s+[a-zA-Z]+\s+[0-9]{4})/i', $joined, $m)) {
                    $headerInfo['periode_mulai'] = trim($m[1]);
                }
                if (preg_match('/PERIODE\s*AKHIR\s*[:\t]?\s*([0-9]{1,2}\s+[a-zA-Z]+\s+[0-9]{4})/i', $joined, $m)) {
                    $headerInfo['periode_akhir'] = trim($m[1]);
                }

                if (str_contains($joined, 'UPT') && (str_contains($joined, 'JENIS PRODUK') || str_contains($joined, 'NOMOR IZIN EDAR'))) {
                    $detectedColumns = $rowValues;
                    $headerRowFound = true;
                    continue;
                }
            } else {
                $nonEmpty = array_filter($rowValues, fn ($v) => $v !== '' && $v !== null);
                if (empty($nonEmpty)) {
                    continue;
                }

                $mappedRow = [];
                foreach (self::KOLOM_26 as $keyIdx => $key) {
                    $mappedRow[$key] = $rowValues[$keyIdx] ?? null;
                }

                $rows[] = $mappedRow;
            }
        }

        return [
            'header' => $headerInfo,
            'columns' => $detectedColumns,
            'rows' => $rows,
        ];
    }

    /**
     * Parse Indonesian date formatted e.g. "28 Des 2025" or "01 Agu 2026" or "15 Mei 2026" to Y-m-d.
     */
    public static function parseIndonesianDate(?string $dateStr): ?Carbon
    {
        if (empty($dateStr)) {
            return null;
        }

        $dateStr = trim($dateStr);

        $bulanMap = [
            'jan' => 1, 'januari' => 1,
            'feb' => 2, 'februari' => 2,
            'mar' => 3, 'maret' => 3,
            'apr' => 4, 'april' => 4,
            'mei' => 5,
            'jun' => 6, 'juni' => 6,
            'jul' => 7, 'juli' => 7,
            'agu' => 8, 'agt' => 8, 'agustus' => 8,
            'sep' => 9, 'september' => 9,
            'okt' => 10, 'oktober' => 10,
            'nov' => 11, 'november' => 11,
            'des' => 12, 'desember' => 12,
        ];

        // Format e.g. "28 Des 2025" or "28-12-2025" or "2025-12-28"
        if (preg_match('/^(\d{1,2})[\s\-\/]+([a-zA-Z]+)[\s\-\/]+(\d{4})$/', $dateStr, $matches)) {
            $day = (int) $matches[1];
            $monthName = strtolower($matches[2]);
            $year = (int) $matches[3];

            if (isset($bulanMap[$monthName])) {
                $month = $bulanMap[$monthName];
                if (checkdate($month, $day, $year)) {
                    return Carbon::createFromDate($year, $month, $day)->startOfDay();
                }
            }
        }

        // Standard format fallback
        try {
            return Carbon::parse($dateStr)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
