<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\Upt;
use App\Models\User;
use App\Services\Iklan\DatasetService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportEvaluatorController extends Controller
{
    public function __construct(
        protected DatasetService $datasetService
    ) {}

    public function index(Request $request): View
    {
        [$baseQuery, $filters] = $this->buildFilteredQuery($request);

        $allMatchingRows = $baseQuery
            ->select([
                'id',
                'dataset_id',
                'evaluator_id',
                'nomor_baris',
                'no',
                'upt',
                'tanggal_evaluasi_pusat',
                'kesimpulan_pusat',
                'kesesuaian_evaluasi',
                'kualitas_laporan',
                'ketepatan_waktu',
                'created_at',
                'updated_at',
            ])
            ->with([
                'dataset:id,nama_file_asli,komoditi,periode_bulan,periode_tahun,uploaded_by,status',
                'dataset.uploader:id,name,role',
                'evaluator:id,name,role',
            ])
            ->orderBy('updated_at', 'desc')
            ->orderBy('dataset_id', 'desc')
            ->orderBy('nomor_baris', 'asc')
            ->get();

        // Susun Rekapitulasi Pengerjaan Evaluator (1 baris per Evaluator dengan akumulasi 3 komoditi dan daftar UPT)
        $rekapRows = $this->buildEvaluatorSummary($allMatchingRows);

        // Data pendukung dropdown filter
        $evaluators = User::whereIn('role', ['evaluator', 'admin'])
            ->where('is_active', true)
            ->orderBy('role', 'desc')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'role']);

        $uptList = Upt::orderBy('kode')->get(['kode', 'nama']);

        $availableYears = Dataset::select('periode_tahun')
            ->distinct()
            ->orderBy('periode_tahun', 'desc')
            ->pluck('periode_tahun')
            ->toArray();

        if (! in_array(now()->year, $availableYears)) {
            $availableYears[] = now()->year;
            rsort($availableYears);
        }

        return view('report_evaluator.index', [
            'rekapGroups' => $rekapRows,
            'filters' => $filters,
            'evaluators' => $evaluators,
            'uptList' => $uptList,
            'availableYears' => $availableYears,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$baseQuery] = $this->buildFilteredQuery($request);

        $rows = $baseQuery
            ->select([
                'id',
                'dataset_id',
                'evaluator_id',
                'nomor_baris',
                'no',
                'upt',
                'tanggal_evaluasi_pusat',
                'kesimpulan_pusat',
                'kesesuaian_evaluasi',
                'kualitas_laporan',
                'ketepatan_waktu',
                'created_at',
                'updated_at',
            ])
            ->with([
                'dataset:id,nama_file_asli,komoditi,periode_bulan,periode_tahun,uploaded_by,status',
                'dataset.uploader:id,name,role',
                'evaluator:id,name,role',
            ])
            ->orderBy('updated_at', 'desc')
            ->orderBy('dataset_id', 'desc')
            ->orderBy('nomor_baris', 'asc')
            ->get();

        $rekapRows = $this->buildEvaluatorSummary($rows);

        $filename = 'Rekap_Report_Evaluator_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($rekapRows) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Rekap Evaluator');

            $headers = [
                'A1' => 'NO',
                'B1' => 'EVALUATOR',
                'C1' => 'UPT',
                'D1' => 'JUMLAH BARIS',
                'E1' => 'TERAKHIR MENGERJAKAN',
            ];

            foreach ($headers as $cell => $text) {
                $sheet->setCellValue($cell, $text);
            }

            $sheet->getStyle('A1:E1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            ]);

            $rowNum = 2;
            foreach ($rekapRows as $idx => $item) {
                $uptCount = max(1, count($item['upt_list']));
                $startRow = $rowNum;
                $endRow = $rowNum + $uptCount - 1;

                $waktuFormatted = "{$item['hari_formatted']}, {$item['tanggal_formatted']} ({$item['jam_terakhir']} WIB)";

                if (empty($item['upt_list'])) {
                    $sheet->setCellValue("A{$startRow}", $idx + 1);
                    $sheet->setCellValue("B{$startRow}", $item['evaluator_name']);
                    $sheet->setCellValue("C{$startRow}", 'Belum ada UPT dikerjakan');
                    $sheet->setCellValue("D{$startRow}", 0);
                    $sheet->setCellValue("E{$startRow}", $waktuFormatted);
                } else {
                    foreach ($item['upt_list'] as $uIdx => $u) {
                        $currentRow = $startRow + $uIdx;
                        $sheet->setCellValue("C{$currentRow}", ($uIdx + 1) . ". {$u['nama']}");
                    }

                    $sheet->setCellValue("A{$startRow}", $idx + 1);
                    $sheet->setCellValue("B{$startRow}", $item['evaluator_name']);
                    $sheet->setCellValue("D{$startRow}", $item['total_baris']);
                    $sheet->setCellValue("E{$startRow}", $waktuFormatted);

                    if ($uptCount > 1) {
                        $sheet->mergeCells("A{$startRow}:A{$endRow}");
                        $sheet->mergeCells("B{$startRow}:B{$endRow}");
                        $sheet->mergeCells("D{$startRow}:D{$endRow}");
                        $sheet->mergeCells("E{$startRow}:E{$endRow}");
                    }
                }

                $rowNum = $endRow + 1;
            }

            if ($rowNum > 2) {
                $lastRow = $rowNum - 1;
                $sheet->getStyle("A2:E{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Bangun query dasar DataMentah sesuai filter Report Evaluator.
     *
     * @return array{0: Builder, 1: array<string, mixed>}
     */
    protected function buildFilteredQuery(Request $request): array
    {
        $query = DataMentah::query()
            ->whereHas('dataset')
            ->whereNotNull('evaluator_id');

        $evaluatorId = $request->query('evaluator_id');
        $upt = $request->query('upt');
        $komoditi = $request->query('komoditi');
        $tahun = $request->query('tahun');
        $bulan = $request->query('bulan');
        $tanggal = $request->query('tanggal');

        if (! empty($evaluatorId)) {
            $query->where('evaluator_id', (int) $evaluatorId);
        }

        if (! empty($upt)) {
            $selectedUpt = is_numeric($upt)
                ? Upt::where('kode', (int) $upt)->first()
                : Upt::where('nama', $upt)->first();

            if ($selectedUpt) {
                $baseLocation = $this->datasetService->cleanUptName($selectedUpt->nama);
                $query->where(function (Builder $q) use ($selectedUpt, $baseLocation) {
                    if ($baseLocation) {
                        $q->where('upt', 'like', '%' . $baseLocation . '%');
                    }
                    $q->orWhereHas('dataIklan', function (Builder $di) use ($selectedUpt) {
                        $di->where('upt', $selectedUpt->kode);
                    });
                });
            } else {
                $query->where('upt', 'like', '%' . trim($upt) . '%');
            }
        }

        if (! empty($komoditi) && strtoupper($komoditi) !== 'SEMUA') {
            $komUpper = strtoupper($komoditi);
            if (in_array($komUpper, ['OBA', 'OT'])) {
                $query->whereHas('dataset', fn (Builder $d) => $d->whereIn('komoditi', ['OBA', 'OT']));
            } else {
                $query->whereHas('dataset', fn (Builder $d) => $d->where('komoditi', $komUpper));
            }
        }

        if (! empty($tahun)) {
            $query->whereHas('dataset', fn (Builder $d) => $d->where('periode_tahun', (int) $tahun));
        }

        if (! empty($bulan)) {
            $query->whereHas('dataset', fn (Builder $d) => $d->where('periode_bulan', (int) $bulan));
        }

        if (! empty($tanggal)) {
            $query->whereDate('updated_at', $tanggal);
        }

        $filters = [
            'evaluator_id' => $evaluatorId,
            'upt' => $upt,
            'komoditi' => $komoditi,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'tanggal' => $tanggal,
        ];

        return [$query, $filters];
    }

    /**
     * Susun Rekapitulasi Pengerjaan per Evaluator (1 baris per Evaluator)
     * menggabungkan UPT yang dikerjakan dan mengakumulasi progres seluruh komoditi (OBA, OK, SK).
     */
    protected function buildEvaluatorSummary(Collection $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $evaluator = $row->evaluator;
            if (! $evaluator) {
                continue;
            }
            $evaluatorId = $evaluator->id;
            $evaluatorName = $evaluator->name;
            $evaluatorRole = $evaluator->role ?? 'evaluator';

            $waktu = $row->updated_at ?? $row->created_at ?? now();

            $uptName = trim((string) ($row->upt ?? ''));
            if ($uptName === '') {
                $uptName = 'UPT Belum Tercantum';
            }

            $rawKomoditi = $row->dataset?->komoditi;
            $komoditi = in_array($rawKomoditi, ['OBA', 'OT']) ? 'OBA' : ($rawKomoditi ?? 'Lainnya');

            if (! isset($grouped[$evaluatorId])) {
                $grouped[$evaluatorId] = [
                    'evaluator_id' => $evaluatorId,
                    'evaluator_name' => $evaluatorName,
                    'evaluator_role' => $evaluatorRole,
                    'latest_timestamp' => $waktu->timestamp,
                    'hari_formatted' => $waktu->translatedFormat('l'),
                    'tanggal_formatted' => $waktu->translatedFormat('d F Y'),
                    'jam_terakhir' => $waktu->format('H:i:s'),
                    'total_baris' => 0,
                    'wajib_isi' => 0,
                    'selesai_manual' => 0,
                    'upt_map' => [],
                    'komoditi_stats' => [
                        'OBA' => ['total' => 0, 'wajib_isi' => 0, 'selesai' => 0, 'persentase' => 0],
                        'OK' => ['total' => 0, 'wajib_isi' => 0, 'selesai' => 0, 'persentase' => 0],
                        'SK' => ['total' => 0, 'wajib_isi' => 0, 'selesai' => 0, 'persentase' => 0],
                    ],
                ];
            }

            if ($waktu->timestamp > $grouped[$evaluatorId]['latest_timestamp']) {
                $grouped[$evaluatorId]['latest_timestamp'] = $waktu->timestamp;
                $grouped[$evaluatorId]['hari_formatted'] = $waktu->translatedFormat('l');
                $grouped[$evaluatorId]['tanggal_formatted'] = $waktu->translatedFormat('d F Y');
                $grouped[$evaluatorId]['jam_terakhir'] = $waktu->format('H:i:s');
            }

            $isMenunggu = empty($row->kesimpulan_pusat) || trim((string) $row->kesimpulan_pusat) === '-';
            $isComplete = ! is_null($row->kualitas_laporan) && ! is_null($row->ketepatan_waktu);

            $grouped[$evaluatorId]['total_baris']++;

            if (! isset($grouped[$evaluatorId]['upt_map'][$uptName])) {
                $grouped[$evaluatorId]['upt_map'][$uptName] = [
                    'nama' => $uptName,
                    'total' => 0,
                    'selesai' => 0,
                ];
            }
            $grouped[$evaluatorId]['upt_map'][$uptName]['total']++;

            if (isset($grouped[$evaluatorId]['komoditi_stats'][$komoditi])) {
                $grouped[$evaluatorId]['komoditi_stats'][$komoditi]['total']++;
            }

            if (! $isMenunggu) {
                $grouped[$evaluatorId]['wajib_isi']++;
                if (isset($grouped[$evaluatorId]['komoditi_stats'][$komoditi])) {
                    $grouped[$evaluatorId]['komoditi_stats'][$komoditi]['wajib_isi']++;
                }
                if ($isComplete) {
                    $grouped[$evaluatorId]['selesai_manual']++;
                    $grouped[$evaluatorId]['upt_map'][$uptName]['selesai']++;
                    if (isset($grouped[$evaluatorId]['komoditi_stats'][$komoditi])) {
                        $grouped[$evaluatorId]['komoditi_stats'][$komoditi]['selesai']++;
                    }
                }
            }
        }

        $result = [];
        foreach ($grouped as $item) {
            $item['persentase'] = $item['wajib_isi'] > 0
                ? (int) round(($item['selesai_manual'] / $item['wajib_isi']) * 100)
                : ($item['total_baris'] > 0 ? 0 : 100);

            foreach (['OBA', 'OK', 'SK'] as $kom) {
                $kTotal = $item['komoditi_stats'][$kom]['total'] ?? 0;
                $kWajib = $item['komoditi_stats'][$kom]['wajib_isi'] ?? 0;
                $kSelesai = $item['komoditi_stats'][$kom]['selesai'] ?? 0;

                $item['komoditi_stats'][$kom]['persentase'] = $kWajib > 0
                    ? (int) round(($kSelesai / $kWajib) * 100)
                    : ($kTotal > 0 ? 0 : 0);
            }

            // Urutkan UPT: UPT yang telah/sedang dikerjakan muncul duluan, lalu abjad nama
            $uptList = array_values($item['upt_map']);
            usort($uptList, function ($a, $b) {
                if ($a['selesai'] !== $b['selesai']) {
                    return $b['selesai'] <=> $a['selesai'];
                }
                return strcmp($a['nama'], $b['nama']);
            });
            $item['upt_list'] = $uptList;
            unset($item['upt_map']);

            $result[] = $item;
        }

        usort($result, fn ($a, $b) => strcmp($a['evaluator_name'], $b['evaluator_name']));

        return $result;
    }
}
