<?php

namespace App\Actions\Iklan;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\Iklan\DashboardService;
use App\Services\Iklan\StandardizationService;
use Exception;
use Illuminate\Validation\ValidationException;

class UpdateManualEvaluationAction
{
    public function __construct(
        protected StandardizationService $standardizationService
    ) {}

    /**
     * Update individual manual cell evaluation.
     */
    public function updateCell(Dataset $dataset, DataMentah $item, string $kolom, mixed $nilai, ?User $user): array
    {
        if ((int) $item->dataset_id !== (int) $dataset->id) {
            throw new Exception('Data tidak ditemukan pada dataset ini.', 404);
        }

        // 1. Penugasan Evaluator per Baris
        if ($kolom === 'evaluator_id') {
            if ($nilai !== '' && ! is_null($nilai)) {
                if (! User::where('id', $nilai)->exists()) {
                    throw ValidationException::withMessages(['nilai' => 'Evaluator yang dipilih tidak valid.']);
                }
                $nilai = (int) $nilai;
            } else {
                $nilai = null;
            }

            if ($user && ! $user->isAdmin()) {
                if ($nilai !== null && $nilai !== (int) $user->id) {
                    throw new Exception('Akses Ditolak: Anda hanya berwenang menugaskan baris ke akun Anda sendiri.', 403);
                }
                if ($nilai === null && $item->evaluator_id !== null && (int) $item->evaluator_id !== (int) $user->id) {
                    throw new Exception('Akses Ditolak: Anda tidak dapat melepas penugasan evaluator lain.', 403);
                }
            }

            $waktu = now();
            $item->update([
                'evaluator_id' => $nilai,
                'updated_at' => $waktu,
            ]);

            if (! $item->dataIklan) {
                $this->standardizationService->standardizeDataset($dataset);
                $item->unsetRelation('dataIklan');
            }

            if ($item->dataIklan) {
                $item->dataIklan->update([
                    'evaluator_id' => $nilai,
                    'updated_at' => $waktu,
                ]);
            }

            $this->standardizationService->syncEvaluatedRows($dataset);
            $evaluatorName = $nilai ? User::find($nilai)?->name : 'Belum Ditugaskan';

            return [
                'success' => true,
                'message' => 'Penugasan evaluator tersimpan',
                'evaluator_id' => $nilai,
                'evaluator_name' => $evaluatorName,
                'waktu' => $this->formatWaktu($waktu),
            ];
        }

        // 2. Pengisian Kolom Manual Evaluasi
        if ($user && ! $user->isAdmin()) {
            if ($item->evaluator_id !== null) {
                if ((int) $item->evaluator_id !== (int) $user->id) {
                    $otherName = $item->evaluator?->name ?? 'evaluator lain';
                    throw new Exception("Akses Ditolak: Baris ini telah diambil/dikerjakan oleh {$otherName}.", 403);
                }
            } else {
                if ($dataset->uploaded_by !== null && (int) $dataset->uploaded_by !== (int) $user->id && ! $dataset->uploader?->isAdmin()) {
                    throw new Exception('Akses Ditolak: Anda tidak memiliki akses untuk mengubah baris pada dataset milik evaluator lain.', 403);
                }
            }
        }

        $validatedNilai = in_array((int) $nilai, [1, 2], true) ? (int) $nilai : null;
        $assignedEvaluatorId = $item->evaluator_id;
        if ($assignedEvaluatorId === null && $user?->isEvaluator() && $validatedNilai !== null) {
            $assignedEvaluatorId = $user->id;
        }

        $waktu = now();
        $item->update([
            $kolom => $validatedNilai,
            'evaluator_id' => $assignedEvaluatorId,
            'updated_at' => $waktu,
        ]);

        if (! $item->dataIklan) {
            $this->standardizationService->standardizeDataset($dataset);
            $item->unsetRelation('dataIklan');
        }

        if ($item->dataIklan) {
            $item->dataIklan->update([
                $kolom => $validatedNilai,
                'evaluator_id' => $assignedEvaluatorId,
                'updated_at' => $waktu,
            ]);
        }

        $this->standardizationService->syncEvaluatedRows($dataset);
        DashboardService::bumpCacheVersion();

        return [
            'success' => true,
            'message' => 'Tersimpan',
            'evaluator_id' => $assignedEvaluatorId,
            'is_manual_complete' => $item->isManualComplete(),
            'waktu' => $this->formatWaktu($waktu),
        ];
    }

    /**
     * Bulk update manual evaluation cells.
     */
    public function bulkUpdate(Dataset $dataset, string $kolom, mixed $rawNilai, ?string $cakupan, ?array $rowIds, ?User $user): array
    {
        if ($kolom === 'evaluator_id') {
            if ($rawNilai !== '' && ! is_null($rawNilai)) {
                if (! User::where('id', $rawNilai)->exists()) {
                    throw ValidationException::withMessages(['nilai' => 'Evaluator yang dipilih tidak valid.']);
                }
                $nilaiVal = (int) $rawNilai;
            } else {
                $nilaiVal = null;
            }
        } else {
            $nilaiVal = in_array((int) $rawNilai, [1, 2], true) ? (int) $rawNilai : null;
        }

        if (! empty($rowIds)) {
            $query = DataMentah::where('dataset_id', $dataset->id)
                ->whereIn('id', $rowIds);
        } else {
            $query = DataMentah::where('dataset_id', $dataset->id)
                ->whereNotNull('kesimpulan_pusat')
                ->where('kesimpulan_pusat', '!=', '')
                ->where('kesimpulan_pusat', '!=', '-');

            if (($cakupan ?? 'semua_dievaluasi') === 'belum_terisi') {
                $query->whereNull($kolom);
            }
        }

        // Jika bukan Administrator, batasi hanya pada baris milik sendiri atau baris yang belum diambil siapapun
        if ($user && ! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('evaluator_id')
                    ->orWhere('evaluator_id', $user->id);
            });
        }

        $updatedIds = $query->pluck('id')->toArray();
        $updatedCount = count($updatedIds);
        $waktu = now();
        $autoEvaluatorId = null;

        if ($updatedCount > 0) {
            if (DataIklan::where('dataset_id', $dataset->id)->count() !== $dataset->dataMentah()->count()) {
                $this->standardizationService->standardizeDataset($dataset);
            }

            if ($kolom === 'evaluator_id') {
                DataMentah::whereIn('id', $updatedIds)->update([
                    'evaluator_id' => $nilaiVal,
                    'updated_at' => $waktu,
                ]);

                DataIklan::whereIn('data_mentah_id', $updatedIds)->update([
                    'evaluator_id' => $nilaiVal,
                    'updated_at' => $waktu,
                ]);
            } else {
                DataMentah::whereIn('id', $updatedIds)->update([
                    $kolom => $nilaiVal,
                    'updated_at' => $waktu,
                ]);

                DataIklan::whereIn('data_mentah_id', $updatedIds)->update([
                    $kolom => $nilaiVal,
                    'updated_at' => $waktu,
                ]);

                if ($nilaiVal !== null && $user && $user->isEvaluator()) {
                    $autoEvaluatorId = $user->id;
                    DataMentah::whereIn('id', $updatedIds)
                        ->whereNull('evaluator_id')
                        ->update(['evaluator_id' => $autoEvaluatorId, 'updated_at' => $waktu]);

                    DataIklan::whereIn('data_mentah_id', $updatedIds)
                        ->where(function ($q) use ($autoEvaluatorId) {
                            $q->whereNull('evaluator_id')
                                ->orWhere('evaluator_id', $autoEvaluatorId);
                        })
                        ->update(['evaluator_id' => $autoEvaluatorId, 'updated_at' => $waktu]);
                }
            }

            $this->standardizationService->syncEvaluatedRows($dataset);
        }

        $namaKolom = match ($kolom) {
            'kesesuaian_evaluasi' => 'Kesesuaian Evaluasi',
            'kualitas_laporan' => 'Kualitas Laporan',
            'ketepatan_waktu' => 'Ketepatan Waktu',
            'evaluator_id' => 'Evaluator',
            default => $kolom,
        };

        DashboardService::bumpCacheVersion();

        return [
            'success' => true,
            'message' => "Berhasil menerapkan {$namaKolom} ke {$updatedCount} baris.",
            'updated_count' => $updatedCount,
            'auto_evaluator_id' => $autoEvaluatorId,
            'nama_kolom' => $namaKolom,
            'nilai_val' => $nilaiVal,
            'waktu' => $this->formatWaktu($waktu),
        ];
    }

    protected function formatWaktu(\Illuminate\Support\Carbon $waktu): array
    {
        return [
            'hari' => $waktu->translatedFormat('l'),
            'tanggal' => $waktu->format('d'),
            'bulan' => $waktu->translatedFormat('F'),
            'tahun' => $waktu->format('Y'),
            'jam' => $waktu->format('H:i:s'),
            'full' => $waktu->translatedFormat('d F Y') . ' ' . $waktu->format('H:i:s') . ' WIB',
        ];
    }
}
