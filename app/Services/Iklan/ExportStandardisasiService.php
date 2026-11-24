<?php

namespace App\Services\Iklan;

use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportStandardisasiService
{
    public const HEADERS = [
        'NO',
        'UPT',
        'PERIODE BULAN',
        'JENIS PRODUK',
        'ASAL PRODUK',
        'KLAIM',
        'KATEGORI MEDIA',
        'KATEGORI IKLAN',
        'NAMA MEDIA',
        'PENCANTUMAN NIE',
        'KESESUAIAN NIE',
        'HASIL EVALUASI',
        'RINCIAN TMK',
        'JENIS TMK',
        'PEMBUAT IKLAN',
        'KESESUAIAN EVALUASI',
        'KUALITAS LAPORAN',
        'KETEPATAN WAKTU',
        'EVALUATOR',
        'WAKTU',
    ];

    /**
     * Generate and stream Excel download for standardized data.
     */
    public function download(Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data All');

            // 1. Write Header Row (Row 1)
            $colLetters = range('A', 'T');
            foreach (self::HEADERS as $idx => $headerText) {
                $col = $colLetters[$idx];
                $cellCoordinate = $col.'1';
                $sheet->setCellValue($cellCoordinate, $headerText);

                // Default header styling: Yellow
                $sheet->getStyle($cellCoordinate)->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle($cellCoordinate)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // Base header color (Yellow #FFF59D)
                $fillColor = 'FFF59D';

                // Soft tints matching Data All
                if ($headerText === 'JENIS PRODUK' || $headerText === 'KLAIM') {
                    $fillColor = 'FCE4EC'; // soft pink
                } elseif ($headerText === 'KATEGORI IKLAN') {
                    $fillColor = 'E8F5E9'; // soft green
                } elseif ($headerText === 'HASIL EVALUASI') {
                    $fillColor = 'E0F7FA'; // soft cyan
                } elseif (in_array($headerText, ['KESESUAIAN EVALUASI', 'KUALITAS LAPORAN', 'KETEPATAN WAKTU'])) {
                    $fillColor = 'FFCDD2'; // soft red manual
                } elseif ($headerText === 'EVALUATOR') {
                    $fillColor = 'BBDEFB'; // soft blue
                } elseif ($headerText === 'WAKTU') {
                    $fillColor = 'C5CAE9'; // soft indigo realtime waktu
                }

                $sheet->getStyle($cellCoordinate)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($fillColor);
            }

            $sheet->getRowDimension(1)->setRowHeight(28);

            // 2. Stream Data in Chunks
            $rowNum = 2;
            $query->with(['evaluator', 'dataMentah.evaluator'])->chunk(500, function ($records) use ($sheet, &$rowNum, $colLetters) {
                foreach ($records as $item) {
                    $evaluatorName = $item->evaluator?->name
                        ?? $item->dataMentah?->evaluator?->name
                        ?? '-';

                    $waktuProses = $item->updated_at ?? $item->created_at ?? ($item->evaluator?->last_login_at ?? now());
                    $waktuStr = $waktuProses->translatedFormat('l, d F Y') . ' ' . $waktuProses->format('H:i:s') . ' WIB';

                    $data = [
                        $item->no,
                        $item->upt,
                        $item->periode_bulan,
                        $item->jenis_produk,
                        $item->asal_produk,
                        $item->klaim,
                        $item->kategori_media,
                        $item->kategori_iklan,
                        $item->nama_media !== null ? $item->nama_media : '-',
                        $item->pencantuman_nie !== null ? $item->pencantuman_nie : '-',
                        $item->kesesuaian_nie !== null ? $item->kesesuaian_nie : '-',
                        $item->hasil_evaluasi !== null ? $item->hasil_evaluasi : '',
                        $item->rincian_tmk !== null ? $item->rincian_tmk : '',
                        $item->jenis_tmk !== null ? $item->jenis_tmk : '',
                        $item->pembuat_iklan,
                        $item->kesesuaian_evaluasi,
                        $item->kualitas_laporan,
                        $item->ketepatan_waktu,
                        $evaluatorName,
                        $waktuStr,
                    ];

                    foreach ($data as $idx => $val) {
                        $col = $colLetters[$idx];
                        $coord = $col.$rowNum;

                        if (is_numeric($val) && $idx < 18) {
                            $sheet->setCellValueExplicit($coord, (int) $val, DataType::TYPE_NUMERIC);
                        } else {
                            $sheet->setCellValueExplicit($coord, (string) $val, DataType::TYPE_STRING);
                        }
                    }

                    $rowNum++;
                }
            });

            if ($rowNum > 2) {
                $lastRow = $rowNum - 1;
                $sheet->getStyle("A2:T{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Set auto column width
            foreach ($colLetters as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Write to output stream
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
