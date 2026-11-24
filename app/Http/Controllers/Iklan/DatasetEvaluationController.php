<?php

namespace App\Http\Controllers\Iklan;

use App\Actions\Iklan\AssignEvaluatorAction;
use App\Actions\Iklan\UpdateManualEvaluationAction;
use App\Http\Controllers\Controller;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\Iklan\DatasetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DatasetEvaluationController extends Controller
{
    public function __construct(
        protected DatasetService $datasetService
    ) {}

    public function updateManualCell(
        Request $request,
        Dataset $dataset,
        DataMentah $item,
        UpdateManualEvaluationAction $action
    ): JsonResponse {
        $validated = $request->validate([
            'kolom' => ['required', 'string', 'in:kesesuaian_evaluasi,kualitas_laporan,ketepatan_waktu,evaluator_id'],
            'nilai' => ['nullable'],
        ]);

        try {
            $result = $action->updateCell($dataset, $item, $validated['kolom'], $validated['nilai'], auth()->user());
            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()->first()], 422);
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [403, 404, 422], true) ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    public function bulkUpdateManual(
        Request $request,
        Dataset $dataset,
        UpdateManualEvaluationAction $action
    ): RedirectResponse|JsonResponse {
        $user = auth()->user();
        if (! $dataset->canBeEditedBy($user)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Akses Ditolak.'], 403);
            }

            return back()->with('error', 'Akses Ditolak.');
        }

        $validated = $request->validate([
            'kolom' => ['required', 'string', 'in:kesesuaian_evaluasi,kualitas_laporan,ketepatan_waktu,evaluator_id'],
            'nilai' => ['nullable'],
            'cakupan' => ['nullable', 'string', 'in:semua_dievaluasi,belum_terisi'],
            'row_ids' => ['nullable', 'array'],
            'row_ids.*' => ['integer'],
        ]);

        try {
            $res = $action->bulkUpdate(
                $dataset,
                $validated['kolom'],
                $validated['nilai'] ?? null,
                $validated['cakupan'] ?? null,
                $validated['row_ids'] ?? null,
                $user
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $res['message'],
                    'updated_count' => $res['updated_count'],
                    'auto_evaluator_id' => $res['auto_evaluator_id'],
                    'waktu' => $res['waktu'],
                ]);
            }

            return back()->with('status', "Berhasil mengisi massal {$res['updated_count']} baris pada kolom {$res['nama_kolom']} dengan kode {$res['nilai_val']}.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->validator->errors()->first()], 422);
            }
            return back()->with('error', $e->validator->errors()->first());
        }
    }

    /**
     * Menugaskan seluruh baris pada UPT tertentu ke evaluator yang dipilih (atau klaim mandiri Opsi A).
     */
    public function assignUpt(
        Request $request,
        Dataset $dataset,
        AssignEvaluatorAction $action
    ): RedirectResponse {
        $validated = $request->validate([
            'upt' => ['required', 'string'],
            'evaluator_id' => ['nullable'],
        ]);

        $evalId = null;
        if ($request->input('action') === 'claim') {
            $evalId = (int) auth()->id();
        } elseif ($request->input('action') !== 'release' && $request->filled('evaluator_id')) {
            $evalId = (int) $request->input('evaluator_id');
        }

        try {
            $res = $action->assignUpt($dataset, $validated['upt'], $evalId, auth()->user(), $this->datasetService);
            if (! empty($res['is_released'])) {
                $msg = "Klaim pada UPT '{$res['upt_display']}' berhasil dilepas ({$res['affected']} baris). UPT kini tersedia untuk diambil evaluator lain.";
            } else {
                $msg = "Berhasil mengklaim/menugaskan {$res['affected']} baris data pada UPT '{$res['upt_display']}' kepada {$res['evaluator_name']}.";
            }

            $returnUrl = $request->input('return_url');
            if ($returnUrl && str_starts_with($returnUrl, url('/'))) {
                return redirect($returnUrl)->with('status', $msg);
            }

            return back()->with('status', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Sinkronkan penugasan baris ke pemilik dataset (Admin atau Pemilik Dataset).
     */
    public function redistributeEvaluator(
        Dataset $dataset,
        AssignEvaluatorAction $action
    ): RedirectResponse {
        try {
            $ownerName = $action->redistributeToOwner($dataset, auth()->user());
            return back()->with('status', "Seluruh baris berhasil disinkronkan ke pemilik dataset ({$ownerName}).");
        } catch (\Throwable $e) {
            if ($e->getCode() === 403) {
                abort(403, $e->getMessage());
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mengalihkan kepemilikan dataset beserta seluruh barisnya ke evaluator lain (khusus Admin).
     */
    public function reassignEvaluatorRows(
        Request $request,
        Dataset $dataset,
        AssignEvaluatorAction $action
    ): RedirectResponse {
        $targetId = $request->input('new_owner_id') ?? $request->input('to_evaluator_id');
        if (! $targetId || ! User::where('id', $targetId)->exists()) {
            return back()->with('error', 'Evaluator tujuan tidak valid.');
        }

        try {
            $res = $action->transferOwnership($dataset, (int) $targetId, auth()->user());
            return back()->with('status', "Berhasil mengalihkan kepemilikan dataset ke {$res['to_user_name']} ({$res['affected']} baris).");
        } catch (\Throwable $e) {
            if ($e->getCode() === 403) {
                abort(403, $e->getMessage());
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
