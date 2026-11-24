<?php

namespace App\Actions\Iklan;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\Upt;
use App\Models\User;
use App\Services\Iklan\DashboardService;
use App\Services\Iklan\DatasetService;
use Exception;

class AssignEvaluatorAction
{
    /**
     * Menugaskan seluruh baris pada UPT tertentu ke evaluator yang dipilih (atau klaim mandiri Opsi A).
     */
    public function assignUpt(Dataset $dataset, string $uptVal, ?int $evaluatorId, User $user, DatasetService $datasetService): array
    {
        if ($dataset->isFinal()) {
            throw new Exception('Dataset sudah berstatus Final dan terkunci, penugasan UPT tidak dapat diubah.');
        }

        $selectedUpt = is_numeric($uptVal)
            ? Upt::where('kode', (int) $uptVal)->first()
            : Upt::where('nama', $uptVal)->first();

        $uptDisplay = $selectedUpt ? $selectedUpt->nama : $uptVal;

        $buildMatchQuery = function () use ($dataset, $selectedUpt, $datasetService, $uptVal) {
            $query = DataMentah::where('dataset_id', $dataset->id);
            if ($selectedUpt) {
                $baseLocation = $datasetService->cleanUptName($selectedUpt->nama);
                $query->where(function ($q) use ($selectedUpt, $baseLocation) {
                    if ($baseLocation) {
                        $q->where('upt', 'like', '%' . $baseLocation . '%');
                    }
                    $q->orWhereHas('dataIklan', function ($di) use ($selectedUpt) {
                        $di->where('upt', $selectedUpt->kode);
                    });
                });
            } else {
                $query->where('upt', $uptVal);
            }
            return $query;
        };

        // 1. Kasus Melepas Penugasan / Batalkan Klaim UPT ($evaluatorId === null)
        if ($evaluatorId === null) {
            if (! $user->isAdmin()) {
                $isClaimedByAnother = $buildMatchQuery()
                    ->whereNotNull('evaluator_id')
                    ->where('evaluator_id', '!=', $user->id)
                    ->exists();

                if ($isClaimedByAnother) {
                    throw new Exception("Akses Ditolak: Anda tidak dapat melepas klaim UPT yang dimiliki oleh evaluator lain.", 403);
                }
            }

            $rawIds = $buildMatchQuery()->pluck('id');
            $affected = DataMentah::whereIn('id', $rawIds)->update(['evaluator_id' => null]);
            DataIklan::where('dataset_id', $dataset->id)
                ->whereIn('data_mentah_id', $rawIds)
                ->update(['evaluator_id' => null]);

            app(\App\Services\Iklan\StandardizationService::class)->syncEvaluatedRows($dataset);
            DashboardService::bumpCacheVersion();

            return [
                'affected' => $affected,
                'upt_display' => $uptDisplay,
                'evaluator_name' => 'Tersedia (Klaim Dilepas)',
                'is_released' => true,
            ];
        }

        // 2. Kasus Penugasan / Klaim Mandiri
        $evaluator = User::findOrFail($evaluatorId);

        if (! $user->isAdmin()) {
            if ($evaluatorId !== (int) $user->id) {
                throw new Exception('Akses Ditolak: Anda hanya berwenang mengambil UPT untuk akun Anda sendiri.', 403);
            }

            // Opsi A Guard: Cek apakah UPT ini sudah diklaim / dikerjakan oleh evaluator lain
            $otherEvaluatorId = $buildMatchQuery()
                ->whereNotNull('evaluator_id')
                ->where('evaluator_id', '!=', $user->id)
                ->value('evaluator_id');

            if ($otherEvaluatorId) {
                $otherUser = User::find($otherEvaluatorId);
                $otherName = $otherUser?->name ?? 'Evaluator lain';
                throw new Exception("Akses Ditolak: UPT '{$uptDisplay}' sudah diambil/dikerjakan oleh {$otherName}.", 403);
            }
        }

        $rawIds = $buildMatchQuery()->pluck('id');
        $affected = DataMentah::whereIn('id', $rawIds)->update(['evaluator_id' => $evaluator->id]);

        DataIklan::where('dataset_id', $dataset->id)
            ->whereIn('data_mentah_id', $rawIds)
            ->update(['evaluator_id' => $evaluator->id]);

        app(\App\Services\Iklan\StandardizationService::class)->syncEvaluatedRows($dataset);
        DashboardService::bumpCacheVersion();

        return [
            'affected' => $affected,
            'upt_display' => $uptDisplay,
            'evaluator_name' => $evaluator->name,
            'is_released' => false,
        ];
    }

    /**
     * Sinkronkan penugasan baris ke pemilik dataset.
     */
    public function redistributeToOwner(Dataset $dataset, User $user): string
    {
        if (! $dataset->canBeEditedBy($user)) {
            throw new Exception('Akses Ditolak: Anda tidak memiliki hak akses untuk mereset distribusi evaluator dataset ini.', 403);
        }

        if ($dataset->isFinal()) {
            throw new Exception('Dataset sudah berstatus Final dan terkunci.');
        }

        $owner = $dataset->uploader ?? $user;
        if ($owner) {
            $dataset->transferOwnership($owner);
        }

        DashboardService::bumpCacheVersion();

        return $owner->name;
    }

    /**
     * Mengalihkan kepemilikan dataset beserta seluruh barisnya ke evaluator lain (khusus Admin).
     */
    public function transferOwnership(Dataset $dataset, int $toEvaluatorId, User $user): array
    {
        if (! $user->isAdmin()) {
            throw new Exception('Akses Ditolak: Hanya Administrator yang dapat mengalihkan kepemilikan dataset.', 403);
        }

        if ($dataset->isFinal()) {
            throw new Exception('Dataset sudah berstatus Final dan terkunci.');
        }

        $toUser = User::findOrFail($toEvaluatorId);
        $affected = $dataset->transferOwnership($toUser);

        DashboardService::bumpCacheVersion();

        return [
            'affected' => $affected,
            'to_user_name' => $toUser->name,
        ];
    }
}
