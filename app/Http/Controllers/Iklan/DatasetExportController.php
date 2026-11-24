<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\Upt;
use App\Services\Iklan\DatasetService;
use App\Services\Iklan\SiptDatasetReader;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatasetExportController extends Controller
{
    public function __construct(
        protected DatasetService $datasetService
    ) {}

    /**
     * Ekspor Daftar Ringkasan Dataset ke berkas Excel (.xlsx).
     */
    public function exportList(Request $request): StreamedResponse
    {
        $query = Dataset::query()->with('uploader');

        if ($request->filled('komoditi')) {
            $komoditiFilter = $request->query('komoditi');
            if ($komoditiFilter === 'OT' || $komoditiFilter === 'OBA') {
                $query->whereIn('komoditi', ['OBA', 'OT']);
            } elseif ($komoditiFilter === 'OK') {
                $query->where('komoditi', 'OK');
            } elseif ($komoditiFilter === 'SK') {
                $query->where('komoditi', 'SK');
            } else {
                $query->where('komoditi', $komoditiFilter);
            }
        }

        if ($request->filled('tahun')) {
            $query->where('periode_tahun', $request->integer('tahun'));
        }

        if ($request->filled('status')) {
            $statusFilter = $request->query('status');
            if ($statusFilter === 'Sudah Diproses') {
                $query->whereIn('status', ['Final', 'Sudah Diproses']);
            } elseif ($statusFilter === 'Diproses') {
                $query->whereNotIn('status', ['Final', 'Sudah Diproses']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        if ($request->filled('uploaded_by')) {
            $query->where('uploaded_by', $request->integer('uploaded_by'));
        }

        if ($request->filled('upt')) {
            $uptVal = $request->query('upt');
            $selectedUpt = is_numeric($uptVal)
                ? Upt::where('kode', (int) $uptVal)->first()
                : Upt::where('nama', $uptVal)->first();

            if ($selectedUpt) {
                $baseLocation = $this->datasetService->cleanUptName($selectedUpt->nama);
                $query->where(function ($q) use ($selectedUpt, $baseLocation) {
                    $q->whereHas('dataMentah', function ($dm) use ($selectedUpt, $baseLocation) {
                        if ($baseLocation) {
                            $dm->where('upt', 'like', '%' . $baseLocation . '%');
                        }
                        $dm->orWhereHas('dataIklan', fn($di) => $di->where('upt', $selectedUpt->kode));
                    })->orWhereHas('dataIklan', function ($di) use ($selectedUpt) {
                        $di->where('upt', $selectedUpt->kode);
                    });
                });
            }
        }

        $allFiltered = $query->orderBy('id', 'desc')->get();
        $allFilteredIds = $allFiltered->pluck('id')->all();

        $evaluatedCounts = ! empty($allFilteredIds)
            ? DataMentah::whereIn('dataset_id', $allFilteredIds)
                ->whereNotNull('kualitas_laporan')
                ->whereNotNull('ketepatan_waktu')
                ->selectRaw('dataset_id, COUNT(DISTINCT nomor_baris) as total_evaluated')
                ->groupBy('dataset_id')
                ->pluck('total_evaluated', 'dataset_id')
            : collect();

        $groupedCollection = $allFiltered
            ->groupBy(fn (Dataset $d) => $d->group_key)
            ->map(function ($group) use ($evaluatedCounts) {
                $primary = $group->first();
                $totalBaris = (int) $group->max('jumlah_baris');
                $menunggu = (int) $group->max('jumlah_menunggu_evaluasi');
                $wajibEvaluasi = max(0, $totalBaris - $menunggu);
                if ($wajibEvaluasi === 0 && $totalBaris > 0) {
                    $wajibEvaluasi = $totalBaris;
                }

                $uniqueEvaluatedCount = min(
                    $totalBaris,
                    (int) $group->sum(fn (Dataset $ds) => (int) ($evaluatedCounts[$ds->id] ?? 0))
                );
                $progressPercent = $wajibEvaluasi > 0
                    ? min(100, (int) round(($uniqueEvaluatedCount / $wajibEvaluasi) * 100))
                    : 0;
                $isGroupComplete = ($wajibEvaluasi > 0 && $uniqueEvaluatedCount >= $wajibEvaluasi)
                    || $group->contains(fn (Dataset $d) => $d->isFinal());

                $primary->sessions_count = $group->count();
                $primary->jumlah_baris = $totalBaris;
                $primary->wajib_evaluasi = $wajibEvaluasi;
                $primary->evaluated_count = $uniqueEvaluatedCount;
                $primary->progress_percent = $progressPercent;
                $primary->is_group_complete = $isGroupComplete;

                if ($isGroupComplete) {
                    $primary->status = 'Final';
                }

                return $primary;
            })
            ->values();

        $filename = 'Daftar_Dataset_Iklan_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($groupedCollection) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Daftar Dataset');

            $headers = [
                'NO',
                'KOMODITI',
                'PERIODE BULAN',
                'PERIODE TAHUN',
                'NAMA BERKAS ASLI',
                'JUMLAH BARIS',
                'WAJIB EVALUASI',
                'BARIS TEREVALUASI',
                'PROGRES (%)',
                'STATUS',
                'PENGUNGGAH / PIC',
                'TANGGAL UNGGAH',
            ];

            $colLetters = range('A', 'L');
            foreach ($headers as $idx => $hdr) {
                $col = $colLetters[$idx];
                $cell = $col . '1';
                $sheet->setCellValue($cell, $hdr);
                $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
                $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }
            $sheet->getRowDimension(1)->setRowHeight(28);

            $rowIdx = 2;
            foreach ($groupedCollection as $i => $item) {
                $sheet->setCellValueExplicit('A' . $rowIdx, (string) ($i + 1), DataType::TYPE_NUMERIC);
                $sheet->setCellValue('B' . $rowIdx, $item->nama_komoditi);
                $sheet->setCellValue('C' . $rowIdx, $item->nama_bulan);
                $sheet->setCellValue('D' . $rowIdx, $item->periode_tahun);
                $sheet->setCellValue('E' . $rowIdx, $item->nama_file_asli);
                $sheet->setCellValueExplicit('F' . $rowIdx, (string) $item->jumlah_baris, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit('G' . $rowIdx, (string) $item->wajib_evaluasi, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit('H' . $rowIdx, (string) $item->evaluated_count, DataType::TYPE_NUMERIC);
                $sheet->setCellValue('I' . $rowIdx, $item->progress_percent . '%');
                $sheet->setCellValue('J' . $rowIdx, $item->status);
                $sheet->setCellValue('K' . $rowIdx, $item->uploader?->name ?? 'Admin');
                $sheet->setCellValue('L' . $rowIdx, $item->created_at ? $item->created_at->format('Y-m-d H:i') : '-');

                $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('G' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('H' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('I' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('J' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('L' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $rowIdx++;
            }

            foreach ($colLetters as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Ekspor Data Mentah SIPT (26 Kolom SIPT + Kolom Evaluasi Manual) ke Excel (.xlsx).
     */
    public function exportMentah(Dataset $dataset, Request $request): StreamedResponse
    {
        $user = auth()->user();
        $isEvaluator = $user && ! $user->isAdmin();

        $groupDatasets = $dataset->getSiblingSessions();
        $groupIds = $groupDatasets->pluck('id')->all() ?: [$dataset->id];

        $mergedRowMap = DataMentah::whereIn('dataset_id', $groupIds)
            ->whereNotNull('kualitas_laporan')
            ->whereNotNull('ketepatan_waktu')
            ->with('evaluator')
            ->get()
            ->keyBy('nomor_baris');

        $rawQuery = DataMentah::where('dataset_id', $dataset->id)->with('evaluator');

        if ($request->filled('upt')) {
            $uptVal = $request->query('upt');
            $selectedUpt = is_numeric($uptVal)
                ? Upt::where('kode', (int) $uptVal)->first()
                : Upt::where('nama', $uptVal)->first();

            if ($selectedUpt) {
                $baseLocation = $this->datasetService->cleanUptName($selectedUpt->nama);
                $rawQuery->where(function ($q) use ($selectedUpt, $baseLocation) {
                    if ($baseLocation) {
                        $q->where('upt', 'like', '%' . $baseLocation . '%');
                    }
                    $q->orWhereHas('dataIklan', function ($di) use ($selectedUpt) {
                        $di->where('upt', $selectedUpt->kode);
                    });
                });
            }
        }

        $evalFilterId = null;
        if ($request->filled('evaluator_id')) {
            $evalFilterId = $request->integer('evaluator_id');
        } elseif ($isEvaluator && ! $request->has('evaluator_id') && ! $request->has('status_tugas')) {
            $evalFilterId = $user->id;
        }

        if ($evalFilterId) {
            $rawQuery->where('evaluator_id', $evalFilterId);
        }

        $statusTugas = $request->query('status_tugas', 'semua');
        $hideCompleted = $request->boolean('sembunyikan_selesai');

        if ($statusTugas === 'tugas_saya' && $user) {
            $rawQuery->where('evaluator_id', $user->id);
            if ($hideCompleted) {
                $rawQuery->where(function ($q) {
                    $q->whereNull('kualitas_laporan')->orWhereNull('ketepatan_waktu');
                });
            }
        } elseif ($statusTugas === 'belum_diambil') {
            $rawQuery->whereNull('evaluator_id');
        } elseif ($statusTugas === 'selesai') {
            $rawQuery->whereNotNull('kualitas_laporan')->whereNotNull('ketepatan_waktu');
        } elseif ($statusTugas === 'belum_selesai') {
            $rawQuery->where(function ($q) {
                $q->whereNull('kualitas_laporan')->orWhereNull('ketepatan_waktu');
            });
        } elseif ($hideCompleted) {
            $rawQuery->where(function ($q) {
                $q->whereNull('kualitas_laporan')->orWhereNull('ketepatan_waktu');
            });
        }

        if ($request->filled('cari')) {
            $keyword = '%'.trim($request->query('cari')).'%';
            $rawQuery->where(function ($q) use ($keyword) {
                $q->where('upt', 'like', $keyword)
                    ->orWhere('nama_produk', 'like', $keyword)
                    ->orWhere('nomor_izin_edar', 'like', $keyword)
                    ->orWhere('khasiat_indikasi', 'like', $keyword)
                    ->orWhere('pendaftar', 'like', $keyword)
                    ->orWhere('media_iklan', 'like', $keyword)
                    ->orWhere('kesimpulan_upt', 'like', $keyword)
                    ->orWhere('kesimpulan_pusat', 'like', $keyword);
            });
        }

        $rows = $rawQuery->orderBy('nomor_baris', 'asc')->orderBy('id', 'asc')->get();

        foreach ($rows as $row) {
            $src = $mergedRowMap->get($row->nomor_baris);
            if ($src) {
                if ($row->kualitas_laporan === null && $src->kualitas_laporan !== null) {
                    $row->kualitas_laporan = $src->kualitas_laporan;
                }
                if ($row->ketepatan_waktu === null && $src->ketepatan_waktu !== null) {
                    $row->ketepatan_waktu = $src->ketepatan_waktu;
                }
                if ($row->kesesuaian_evaluasi === null && $src->kesesuaian_evaluasi !== null) {
                    $row->kesesuaian_evaluasi = $src->kesesuaian_evaluasi;
                }
                if ($row->evaluator_id === null && $src->evaluator_id !== null) {
                    $row->evaluator_id = $src->evaluator_id;
                    $row->setRelation('evaluator', $src->evaluator);
                }
                if ($src->updated_at) {
                    $row->updated_at = $src->updated_at;
                }
            }
        }

        $filename = 'Data_Mentah_SIPT_' . $dataset->komoditi . '_' . $dataset->nama_bulan . '_' . $dataset->periode_tahun . '.xlsx';

        return response()->streamDownload(function () use ($rows, $dataset) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Mentah SIPT');

            $headers26 = SiptDatasetReader::HEADER_26;
            $evalHeaders = [
                'EVALUATOR',
                'KUALITAS LAPORAN',
                'KETEPATAN WAKTU',
                'KESESUAIAN EVALUASI',
                'WAKTU EVALUASI',
            ];
            $allHeaders = array_merge($headers26, $evalHeaders);

            $colIdx = 1;
            foreach ($allHeaders as $hdr) {
                $cellCoordinate = Coordinate::stringFromColumnIndex($colIdx) . '1';
                $sheet->setCellValue($cellCoordinate, $hdr);

                $isEvalCol = $colIdx > count($headers26);
                $bgColor = $isEvalCol ? '059669' : '1E3A8A';

                $sheet->getStyle($cellCoordinate)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($cellCoordinate)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bgColor);
                $sheet->getStyle($cellCoordinate)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                $colIdx++;
            }
            $sheet->getRowDimension(1)->setRowHeight(30);

            $keys26 = SiptDatasetReader::KOLOM_26;
            $rowNum = 2;
            foreach ($rows as $row) {
                $c = 1;
                foreach ($keys26 as $k) {
                    $cellCoord = Coordinate::stringFromColumnIndex($c) . $rowNum;
                    $val = $row->$k ?? '';
                    if (is_numeric($val) && strlen((string)$val) < 11 && !str_starts_with((string)$val, '0')) {
                        $sheet->setCellValueExplicit($cellCoord, (string)$val, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, (string)$val, DataType::TYPE_STRING);
                    }
                    $c++;
                }

                $evalName = $row->evaluator?->name ?? ($row->evaluator_id ? 'Evaluator #' . $row->evaluator_id : '-');
                $kualitas = match($row->kualitas_laporan) {
                    1 => 'Lengkap & Sesuai Standard',
                    2 => 'Kurang Lengkap / Perlu Revisi',
                    default => '-',
                };
                $ketepatan = match($row->ketepatan_waktu) {
                    1 => 'Tepat Waktu',
                    2 => 'Terlambat',
                    default => '-',
                };
                $kesesuaian = match($row->kesesuaian_evaluasi) {
                    1 => 'Sesuai',
                    2 => 'Tidak Sesuai',
                    default => '-',
                };
                $waktu = $row->updated_at ? $row->updated_at->format('Y-m-d H:i') : '-';

                $evalValues = [$evalName, $kualitas, $ketepatan, $kesesuaian, $waktu];
                foreach ($evalValues as $evVal) {
                    $cellCoord = Coordinate::stringFromColumnIndex($c) . $rowNum;
                    $sheet->setCellValueExplicit($cellCoord, (string)$evVal, DataType::TYPE_STRING);
                    $c++;
                }

                $rowNum++;
            }

            $totalCols = count($allHeaders);
            for ($i = 1; $i <= $totalCols; $i++) {
                $colStr = Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($colStr)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
