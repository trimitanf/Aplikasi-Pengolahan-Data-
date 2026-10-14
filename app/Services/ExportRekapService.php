<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportRekapService
{
    public function download(array $rekap, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rekap) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('rekap');

            $oba = $rekap['oba'];
            $sk = $rekap['sk'];
            $tahun = $rekap['tahun'];
            $updateDateStr = $rekap['tanggal_update']->translatedFormat('d F Y');
            $maxNo = $rekap['max_no'];

            // Header titles (A1:M5)
            $sheet->mergeCells('A1:M1');
            $sheet->setCellValue('A1', 'SUMMARY DATA IKLAN OBAT BAHAN ALAM DAN SUPLEMEN KESEHATAN');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

            $sheet->mergeCells('A2:M2');
            $sheet->setCellValue('A2', "TAHUN {$tahun}");
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);

            $sheet->mergeCells('A3:M3');
            $sheet->setCellValue('A3', "UPDATE " . strtoupper($updateDateStr));
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(10);

            $sheet->mergeCells('A4:M4');
            $sheet->setCellValue('A4', "Nomor 1-{$maxNo}");
            $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(10);

            $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Table Headers (Row 6)
            // Left Table: OBA (A6:F6)
            $sheet->setCellValue('A6', 'IKLAN OBAT BAHAN ALAM');
            $sheet->setCellValue('B6', 'TOTAL');
            $sheet->setCellValue('C6', 'MK');
            $sheet->setCellValue('D6', '% MK');
            $sheet->setCellValue('E6', 'TMK');
            $sheet->setCellValue('F6', '% TMK');

            // Right Table: SK (H6:M6)
            $sheet->setCellValue('H6', 'IKLAN SUPLEMEN KESEHATAN');
            $sheet->setCellValue('I6', 'TOTAL');
            $sheet->setCellValue('J6', 'MK');
            $sheet->setCellValue('K6', '% MK');
            $sheet->setCellValue('L6', 'TMK');
            $sheet->setCellValue('M6', '% TMK');

            $sheet->getStyle('A6:F6')->getFont()->setBold(true);
            $sheet->getStyle('H6:M6')->getFont()->setBold(true);
            $sheet->getStyle('A6:F6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H6:M6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Row 7: Total Pengawasan Iklan
            $sheet->setCellValue('A7', 'Total Pengawasan Iklan');
            $sheet->setCellValue('B7', $oba['total_pengawasan']['total']);
            $sheet->setCellValue('C7', $oba['total_pengawasan']['mk']);
            $sheet->setCellValue('D7', '=IF(B7>0, C7/B7, 0)');
            $sheet->setCellValue('E7', '=B7-C7');
            $sheet->setCellValue('F7', '=IF(B7>0, E7/B7, 0)');

            $sheet->setCellValue('H7', 'Total Pengawasan Iklan');
            $sheet->setCellValue('I7', $sk['total_pengawasan']['total']);
            $sheet->setCellValue('J7', $sk['total_pengawasan']['mk']);
            $sheet->setCellValue('K7', '=IF(I7>0, J7/I7, 0)');
            $sheet->setCellValue('L7', '=I7-J7');
            $sheet->setCellValue('M7', '=IF(I7>0, L7/I7, 0)');

            $sheet->getStyle('A7:F7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF59D');
            $sheet->getStyle('H7:M7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF59D');

            // Asal Produk (Row 8-11)
            $sheet->setCellValue('A8', 'Rincian berdasarkan asal produk');
            $sheet->setCellValue('H8', 'Rincian berdasarkan asal produk');
            $sheet->getStyle('A8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');
            $sheet->getStyle('H8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');

            $asalLabels = [
                9 => ['key' => 'lokal', 'label' => 'a. Lokal'],
                10 => ['key' => 'impor', 'label' => 'b. Impor'],
                11 => ['key' => 'lisensi', 'label' => 'c. Lisensi'],
            ];

            foreach ($asalLabels as $r => $cfg) {
                // OBA
                $sheet->setCellValue("A{$r}", $cfg['label']);
                $sheet->setCellValue("B{$r}", $oba['asal_produk'][$cfg['key']]['total']);
                $sheet->setCellValue("C{$r}", $oba['asal_produk'][$cfg['key']]['mk']);
                $sheet->setCellValue("D{$r}", "=IF(B{$r}>0, C{$r}/B{$r}, 0)");
                $sheet->setCellValue("E{$r}", "=B{$r}-C{$r}");
                $sheet->setCellValue("F{$r}", "=IF(B{$r}>0, E{$r}/B{$r}, 0)");

                // SK
                $sheet->setCellValue("H{$r}", $cfg['label']);
                $sheet->setCellValue("I{$r}", $sk['asal_produk'][$cfg['key']]['total']);
                $sheet->setCellValue("J{$r}", $sk['asal_produk'][$cfg['key']]['mk']);
                $sheet->setCellValue("K{$r}", "=IF(I{$r}>0, J{$r}/I{$r}, 0)");
                $sheet->setCellValue("L{$r}", "=I{$r}-J{$r}");
                $sheet->setCellValue("M{$r}", "=IF(I{$r}>0, L{$r}/I{$r}, 0)");
            }

            // Kategori Media (Row 13-16)
            $sheet->setCellValue('A13', 'Rincian berdasarkan kategori media');
            $sheet->setCellValue('H13', 'Rincian berdasarkan kategori media');
            $sheet->getStyle('A13')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');
            $sheet->getStyle('H13')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');

            $mediaLabels = [
                14 => ['key' => 'audio', 'label' => 'a. Audio'],
                15 => ['key' => 'visual', 'label' => 'b. Visual'],
                16 => ['key' => 'audiovisual', 'label' => 'c. Audiovisual'],
            ];

            foreach ($mediaLabels as $r => $cfg) {
                // OBA
                $sheet->setCellValue("A{$r}", $cfg['label']);
                $sheet->setCellValue("B{$r}", $oba['kategori_media'][$cfg['key']]['total']);
                $sheet->setCellValue("C{$r}", $oba['kategori_media'][$cfg['key']]['mk']);
                $sheet->setCellValue("D{$r}", "=IF(B{$r}>0, C{$r}/B{$r}, 0)");
                $sheet->setCellValue("E{$r}", "=B{$r}-C{$r}");
                $sheet->setCellValue("F{$r}", "=IF(B{$r}>0, E{$r}/B{$r}, 0)");

                // SK
                $sheet->setCellValue("H{$r}", $cfg['label']);
                $sheet->setCellValue("I{$r}", $sk['kategori_media'][$cfg['key']]['total']);
                $sheet->setCellValue("J{$r}", $sk['kategori_media'][$cfg['key']]['mk']);
                $sheet->setCellValue("K{$r}", "=IF(I{$r}>0, J{$r}/I{$r}, 0)");
                $sheet->setCellValue("L{$r}", "=I{$r}-J{$r}");
                $sheet->setCellValue("M{$r}", "=IF(I{$r}>0, L{$r}/I{$r}, 0)");
            }

            // Kategori Iklan (Row 18-26)
            $sheet->setCellValue('A18', 'Rincian berdasarkan kategori iklan');
            $sheet->setCellValue('H18', 'Rincian berdasarkan kategori iklan');
            $sheet->getStyle('A18')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');
            $sheet->getStyle('H18')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');

            $iklanObaLabels = [
                19 => ['key' => 'ecommerce', 'label' => 'a. E-Commerce'],
                20 => ['key' => 'website', 'label' => 'b. Website'],
                21 => ['key' => 'medsos', 'label' => 'c. Media Sosial'],
                22 => ['key' => 'internet_lain', 'label' => 'd. Internet Lain'],
                23 => ['key' => 'tv_nasional', 'label' => 'e. TV Nasional'],
                24 => ['key' => 'penyiaran_lokal', 'label' => 'f. Penyiaran Lokal'],
                25 => ['key' => 'luar_ruang', 'label' => 'g. Luar Ruang'],
                26 => ['key' => 'cetak', 'label' => 'h. Cetak'],
            ];

            foreach ($iklanObaLabels as $r => $cfg) {
                $sheet->setCellValue("A{$r}", $cfg['label']);
                $sheet->setCellValue("B{$r}", $oba['kategori_iklan'][$cfg['key']]['total']);
                $sheet->setCellValue("C{$r}", $oba['kategori_iklan'][$cfg['key']]['mk']);
                $sheet->setCellValue("D{$r}", "=IF(B{$r}>0, C{$r}/B{$r}, 0)");
                $sheet->setCellValue("E{$r}", "=B{$r}-C{$r}");
                $sheet->setCellValue("F{$r}", "=IF(B{$r}>0, E{$r}/B{$r}, 0)");
            }

            $iklanSkLabels = [
                19 => ['key' => 'ecommerce', 'label' => 'a. E-Commerce'],
                20 => ['key' => 'website', 'label' => 'b. Website'],
                21 => ['key' => 'medsos', 'label' => 'c. Media Sosial'],
                22 => ['key' => 'internet_lain', 'label' => 'd. Internet Lain'],
                23 => ['key' => 'tv_nasional', 'label' => 'e. TV Nasional'],
                24 => ['key' => 'luar_ruang', 'label' => 'g. Luar Ruang'],
                25 => ['key' => 'cetak', 'label' => 'h. Cetak'],
            ];

            foreach ($iklanSkLabels as $r => $cfg) {
                $sheet->setCellValue("H{$r}", $cfg['label']);
                $sheet->setCellValue("I{$r}", $sk['kategori_iklan'][$cfg['key']]['total']);
                $sheet->setCellValue("J{$r}", $sk['kategori_iklan'][$cfg['key']]['mk']);
                $sheet->setCellValue("K{$r}", "=IF(I{$r}>0, J{$r}/I{$r}, 0)");
                $sheet->setCellValue("L{$r}", "=I{$r}-J{$r}");
                $sheet->setCellValue("M{$r}", "=IF(I{$r}>0, L{$r}/I{$r}, 0)");
            }

            // Pembuat Iklan (Row 28-30)
            $sheet->setCellValue('A28', 'Rincian berdasarkan pembuat iklan');
            $sheet->setCellValue('H28', 'Rincian berdasarkan pembuat iklan');
            $sheet->getStyle('A28')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');
            $sheet->getStyle('H28')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');

            $sheet->setCellValue('A29', 'a. Pelaku Usaha');
            $sheet->setCellValue('B29', $oba['pembuat_iklan']['pelaku_usaha']['total']);
            $sheet->setCellValue('C29', $oba['pembuat_iklan']['pelaku_usaha']['mk']);
            $sheet->setCellValue('D29', '=IF(B29>0, C29/B29, 0)');
            $sheet->setCellValue('E29', '=B29-C29');
            $sheet->setCellValue('F29', '=IF(B29>0, E29/B29, 0)');

            $sheet->setCellValue('A30', 'b. Perseorangan');
            $sheet->setCellValue('B30', $oba['pembuat_iklan']['perseorangan']['total']);
            $sheet->setCellValue('C30', $oba['pembuat_iklan']['perseorangan']['mk']);
            $sheet->setCellValue('D30', '=IF(B30>0, C30/B30, 0)');
            $sheet->setCellValue('E30', '=B30-C30');
            $sheet->setCellValue('F30', '=IF(B30>0, E30/B30, 0)');

            $sheet->setCellValue('H29', 'a. Pelaku Usaha');
            $sheet->setCellValue('I29', $sk['pembuat_iklan']['pelaku_usaha']['total']);
            $sheet->setCellValue('J29', $sk['pembuat_iklan']['pelaku_usaha']['mk']);
            $sheet->setCellValue('K29', '=IF(I29>0, J29/I29, 0)');
            $sheet->setCellValue('L29', '=I29-J29');
            $sheet->setCellValue('M29', '=IF(I29>0, L29/I29, 0)');

            $sheet->setCellValue('H30', 'b. Perseorangan');
            $sheet->setCellValue('I30', $sk['pembuat_iklan']['perseorangan']['total']);
            $sheet->setCellValue('J30', $sk['pembuat_iklan']['perseorangan']['mk']);
            $sheet->setCellValue('K30', '=IF(I30>0, J30/I30, 0)');
            $sheet->setCellValue('L30', '=I30-J30');
            $sheet->setCellValue('M30', '=IF(I30>0, L30/I30, 0)');

            // Pencantuman NIE (Row 32-34)
            $sheet->setCellValue('A32', 'Pencantuman NIE (E-Commerce)');
            $sheet->setCellValue('H32', 'Pencantuman NIE (E-Commerce)');
            $sheet->getStyle('A32')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');
            $sheet->getStyle('H32')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');

            $sheet->setCellValue('A33', 'a. Ada');
            $sheet->setCellValue('B33', $oba['pencantuman_nie']['ada']);
            $sheet->setCellValue('A34', 'b. Tidak Ada');
            $sheet->setCellValue('B34', '=B19-B33');

            $sheet->setCellValue('H33', 'a. Ada');
            $sheet->setCellValue('I33', $sk['pencantuman_nie']['ada']);
            $sheet->setCellValue('H34', 'b. Tidak Ada');
            $sheet->setCellValue('I34', '=I19-I33');

            // Kesesuaian NIE (Row 36-38)
            $sheet->setCellValue('A36', 'Kesesuaian NIE (E-Commerce)');
            $sheet->setCellValue('H36', 'Kesesuaian NIE (E-Commerce)');
            $sheet->getStyle('A36')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');
            $sheet->getStyle('H36')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9C4');

            $sheet->setCellValue('A37', 'a. Sesuai');
            $sheet->setCellValue('B37', $oba['kesesuaian_nie']['sesuai']);
            $sheet->setCellValue('A38', 'b. Tidak Sesuai');
            $sheet->setCellValue('B38', '=B33-B37');

            $sheet->setCellValue('H37', 'a. Sesuai');
            $sheet->setCellValue('I37', $sk['kesesuaian_nie']['sesuai']);
            $sheet->setCellValue('H38', 'b. Tidak Sesuai');
            $sheet->setCellValue('I38', '=I33-I37');

            // Rincian TMK (Row 40-46)
            $sheet->setCellValue('A40', 'Rincian TMK');
            $sheet->setCellValue('B40', 'TOTAL');
            $sheet->setCellValue('C40', '%');
            $sheet->setCellValue('H40', 'Rincian TMK');
            $sheet->setCellValue('I40', 'TOTAL');
            $sheet->setCellValue('J40', '%');

            $sheet->getStyle('A40:C40')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF59D');
            $sheet->getStyle('H40:J40')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF59D');
            $sheet->getStyle('A40:C40')->getFont()->setBold(true);
            $sheet->getStyle('H40:J40')->getFont()->setBold(true);

            $rincianRows = [
                41 => ['key' => 'tie', 'label' => 'a. Produk tidak terdaftar'],
                42 => ['key' => 'testimoni', 'label' => 'b. Mencantumkan testimoni'],
                43 => ['key' => 'hadiah', 'label' => 'c. Memberikan hadiah'],
                44 => ['key' => 'klaim_berlebihan', 'label' => 'd. Mencantumkan klaim berlebihan'],
                45 => ['key' => 'lain_lain', 'label' => 'e. Lain-lain'],
            ];

            foreach ($rincianRows as $r => $cfg) {
                // OBA
                $sheet->setCellValue("A{$r}", $cfg['label']);
                $sheet->setCellValue("B{$r}", $oba['rincian_tmk'][$cfg['key']]['total']);
                $sheet->setCellValue("C{$r}", "=IF(\$E\$7>0, B{$r}/\$E\$7, 0)");

                // SK
                $sheet->setCellValue("H{$r}", $cfg['label']);
                $sheet->setCellValue("I{$r}", $sk['rincian_tmk'][$cfg['key']]['total']);
                $sheet->setCellValue("J{$r}", "=IF(\$L\$7>0, I{$r}/\$L\$7, 0)");
            }

            // Row 46: Total Rincian TMK
            $sheet->setCellValue('A46', 'Total');
            $sheet->setCellValue('B46', '=SUM(B41:B45)');
            $sheet->setCellValue('C46', '=IF($E$7>0, B46/$E$7, 0)');

            $sheet->setCellValue('H46', 'Total');
            $sheet->setCellValue('I46', '=SUM(I41:I45)');
            $sheet->setCellValue('J46', '=IF($L$7>0, I46/$L$7, 0)');

            // IKU Section (Row 48-50)
            $sheet->setCellValue('A48', "TARGET IKU OBA MK {$tahun}");
            $sheet->setCellValue('B48', $oba['target_iku'] / 100);
            $sheet->setCellValue('A49', "CAPAIAN IKU OBA MK");
            $sheet->setCellValue('B49', '=D7');
            $sheet->setCellValue('A50', "Persentase capaian IKU");
            $sheet->setCellValue('B50', '=IF(B48>0, B49/B48, 0)');

            $sheet->getStyle('A48:B50')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E0F7FA');
            $sheet->getStyle('A48:B50')->getFont()->setBold(true);

            $sheet->setCellValue('H48', "TARGET IKU SK MK {$tahun}");
            $sheet->setCellValue('I48', $sk['target_iku'] / 100);
            $sheet->setCellValue('H49', "CAPAIAN IKU SK MK");
            $sheet->setCellValue('I49', '=K7');
            $sheet->setCellValue('H50', "Persentase capaian IKU");
            $sheet->setCellValue('I50', '=IF(I48>0, I49/I48, 0)');

            $sheet->getStyle('H48:I50')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E0F7FA');
            $sheet->getStyle('H48:I50')->getFont()->setBold(true);

            // Formatting: Number formats
            // Percent columns: D, F, C (rincian TMK), B48:B50, K, M, J (rincian TMK), I48:I50
            $sheet->getStyle('D7:D30')->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('F7:F30')->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('C41:C46')->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('B48:B50')->getNumberFormat()->setFormatCode('0.00%');

            $sheet->getStyle('K7:K30')->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('M7:M30')->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('J41:J46')->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('I48:I50')->getNumberFormat()->setFormatCode('0.00%');

            // Thousands format for integer cells
            $sheet->getStyle('B7:C30')->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E7:E30')->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('B33:B38')->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('B41:B46')->getNumberFormat()->setFormatCode('#,##0');

            $sheet->getStyle('I7:J30')->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('L7:L30')->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I33:I38')->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I41:I46')->getNumberFormat()->setFormatCode('#,##0');

            // Alignment: Numbers centered or right
            $sheet->getStyle('B7:F50')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('I7:M50')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Auto column dimensions
            foreach (range('A', 'M') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
