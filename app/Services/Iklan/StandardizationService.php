<?php

namespace App\Services\Iklan;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\MasterKode;
use App\Models\PemetaanNilai;
use App\Models\Upt;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StandardizationService
{
    protected ?Collection $cachedUpts = null;

    protected ?Collection $cachedMasterKodes = null;

    protected ?Collection $cachedPemetaan = null;

   
    public function standardizeDataset(Dataset $dataset): void
    {
        $this->loadCaches();

        $rawRows = $dataset->dataMentah()->orderBy('nomor_baris', 'asc')->orderBy('id', 'asc')->get();
        if ($rawRows->isEmpty()) {
            return;
        }

      
        $existingRecords = DataIklan::withTrashed()
            ->where('dataset_id', $dataset->id)
            ->get()
            ->keyBy('data_mentah_id');
        $existingKualitas = $existingRecords->pluck('kualitas_laporan', 'data_mentah_id')->toArray();
        $existingKetepatan = $existingRecords->pluck('ketepatan_waktu', 'data_mentah_id')->toArray();
        $existingNo = $existingRecords->pluck('no', 'data_mentah_id')->toArray();

        DB::transaction(function () use ($dataset, $rawRows, $existingKualitas, $existingKetepatan, $existingNo) {
            $rawIds = $rawRows->pluck('id')->all();

            DataIklan::withTrashed()
                ->where('dataset_id', $dataset->id)
                ->whereNotIn('data_mentah_id', $rawIds)
                ->forceDelete();

            DataIklan::onlyTrashed()
                ->where('dataset_id', $dataset->id)
                ->whereIn('data_mentah_id', $rawIds)
                ->restore();

            $batch = [];
            $now = now()->toDateTimeString();

            foreach ($rawRows as $index => $raw) {
                $standardized = $this->standardizeRow($dataset, $raw);

               
                if ($raw->kesesuaian_evaluasi !== $standardized['kesesuaian_evaluasi']) {
                    DataMentah::where('id', $raw->id)->update(['kesesuaian_evaluasi' => $standardized['kesesuaian_evaluasi']]);
                }

               
                $standardized['kualitas_laporan'] = $raw->kualitas_laporan ?? $existingKualitas[$raw->id] ?? null;
                $standardized['ketepatan_waktu'] = $raw->ketepatan_waktu ?? $existingKetepatan[$raw->id] ?? null;

                
                $isRowEvaluated = ! is_null($raw->evaluator_id)
                    && ! is_null($standardized['kualitas_laporan'])
                    && ! is_null($standardized['ketepatan_waktu']);

                $standardized['no'] = (int) ($raw->nomor_baris ?: ($index + 1));
                $standardized['is_final'] = $isRowEvaluated;

                $standardized['dataset_id'] = $dataset->id;
                $standardized['data_mentah_id'] = $raw->id;
                $standardized['evaluator_id'] = $raw->evaluator_id;
                $standardized['created_at'] = $now;
                $standardized['updated_at'] = $now;
                $standardized['catatan_tidak_terpetakan'] = ! empty($standardized['catatan_tidak_terpetakan'])
                    ? json_encode($standardized['catatan_tidak_terpetakan'])
                    : null;

                $batch[] = $standardized;

                if (count($batch) >= 500) {
                    DataIklan::upsert(
                        $batch,
                        ['data_mentah_id'],
                        [
                            'no', 'upt', 'periode_bulan', 'periode_tahun', 'jenis_produk', 'asal_produk',
                            'klaim', 'kategori_media', 'kategori_iklan', 'nama_media', 'pencantuman_nie',
                            'kesesuaian_nie', 'hasil_evaluasi', 'rincian_tmk', 'jenis_tmk', 'pembuat_iklan',
                            'kesesuaian_evaluasi', 'kualitas_laporan', 'ketepatan_waktu',
                            'status_standardisasi', 'catatan_tidak_terpetakan', 'is_final', 'evaluator_id', 'updated_at',
                        ]
                    );
                    $batch = [];
                }
            }

            if (! empty($batch)) {
                DataIklan::upsert(
                    $batch,
                    ['data_mentah_id'],
                    [
                        'no', 'upt', 'periode_bulan', 'periode_tahun', 'jenis_produk', 'asal_produk',
                        'klaim', 'kategori_media', 'kategori_iklan', 'nama_media', 'pencantuman_nie',
                        'kesesuaian_nie', 'hasil_evaluasi', 'rincian_tmk', 'jenis_tmk', 'pembuat_iklan',
                        'kesesuaian_evaluasi', 'kualitas_laporan', 'ketepatan_waktu',
                        'status_standardisasi', 'catatan_tidak_terpetakan', 'is_final', 'evaluator_id', 'updated_at',
                    ]
                );
            }

            $this->refreshDatasetCompletionStatus($dataset);
        });
    }

    
    public function syncEvaluatedRows(Dataset $dataset): void
    {
        $mentahCount = $dataset->dataMentah()->count();
        $iklanCount = DataIklan::where('dataset_id', $dataset->id)->count();

        if ($mentahCount > 0 && $iklanCount !== $mentahCount) {
            $this->standardizeDataset($dataset);

            return;
        }

       
        $hasOutdatedNie = ! $dataset->isFinal() && DataIklan::where('dataset_id', $dataset->id)
            ->whereNotNull('pencantuman_nie')
            ->whereHas('dataMentah', function ($q) {
                $q->whereNull('pencantuman_nomor_izin_edar')
                    ->orWhere('pencantuman_nomor_izin_edar', '')
                    ->orWhere('pencantuman_nomor_izin_edar', '-');
            })
            ->exists();

        if ($hasOutdatedNie) {
            $this->standardizeDataset($dataset);

            return;
        }

        // Sinkronkan data evaluasi manual (evaluator_id, kualitas_laporan, ketepatan_waktu, kesesuaian_evaluasi)
        // langsung dari DataMentah ke DataIklan sesuai data terbaru
        if (DB::getDriverName() === 'sqlite') {
            $evaluatedMentah = DataMentah::where('dataset_id', $dataset->id)
                ->get(['id', 'evaluator_id', 'kualitas_laporan', 'ketepatan_waktu', 'kesesuaian_evaluasi']);
            foreach ($evaluatedMentah as $dm) {
                DataIklan::where('data_mentah_id', $dm->id)->update([
                    'evaluator_id' => $dm->evaluator_id,
                    'kualitas_laporan' => $dm->kualitas_laporan,
                    'ketepatan_waktu' => $dm->ketepatan_waktu,
                    'kesesuaian_evaluasi' => $dm->kesesuaian_evaluasi,
                ]);
            }
        } else {
            DB::table('data_iklan as di')
                ->join('data_mentah as dm', 'di.data_mentah_id', '=', 'dm.id')
                ->where('di.dataset_id', $dataset->id)
                ->update([
                    'di.evaluator_id' => DB::raw('dm.evaluator_id'),
                    'di.kualitas_laporan' => DB::raw('dm.kualitas_laporan'),
                    'di.ketepatan_waktu' => DB::raw('dm.ketepatan_waktu'),
                    'di.kesesuaian_evaluasi' => DB::raw('dm.kesesuaian_evaluasi'),
                ]);
        }

        DataIklan::where('dataset_id', $dataset->id)
            ->whereNotNull('evaluator_id')
            ->whereNotNull('kualitas_laporan')
            ->whereNotNull('ketepatan_waktu')
            ->where('is_final', false)
            ->update(['is_final' => true]);

        DataIklan::where('dataset_id', $dataset->id)
            ->where(function ($q) {
                $q->whereNull('evaluator_id')
                    ->orWhereNull('kualitas_laporan')
                    ->orWhereNull('ketepatan_waktu');
            })
            ->where('is_final', true)
            ->update(['is_final' => false]);

        $this->refreshDatasetCompletionStatus($dataset);
    }

    
    public function refreshDatasetCompletionStatus(Dataset $dataset): void
    {
        $periodDatasetsQuery = Dataset::where('periode_tahun', $dataset->periode_tahun)
            ->where('periode_bulan', $dataset->periode_bulan)
            ->whereNotIn('status', ['Final', 'Gagal', 'Ditolak', 'Terstandardisasi']);

        if (in_array($dataset->komoditi, ['OBA', 'OT'], true)) {
            $periodDatasetsQuery->whereIn('komoditi', ['OBA', 'OT']);
        } else {
            $periodDatasetsQuery->where('komoditi', $dataset->komoditi);
        }

        $periodDatasetIds = $periodDatasetsQuery->pluck('id')->toArray();

        if (! empty($periodDatasetIds)) {
            Dataset::whereIn('id', $periodDatasetIds)->update([
                'status' => 'Terstandardisasi',
            ]);
        }
    }

    
    public function standardizeRow(Dataset $dataset, DataMentah $raw): array
    {
        $this->loadCaches();

        // 1. UPT <- UPT
        $uptCode = $this->resolveUpt($raw->upt);

        // 2. Periode Bulan <- Tanggal Pengambilan Iklan
        $periodeBulan = $this->resolvePeriodeBulan($raw->tanggal_pengambilan_iklan);
        $periodeTahun = $this->resolvePeriodeTahun($raw->tanggal_pengambilan_iklan) ?? (int) $dataset->periode_tahun;

        // 3. Jenis Produk <- Jenis Produk
        $jenisProduk = $this->resolveJenisProduk($raw->jenis_produk);

        // 4. Asal Produk <- Asal Produk
        $asalProduk = $this->resolveAsalProduk($raw->asal_produk);

        // 5. Klaim <- Klasifikasi Klaim (Klasifikasi Iklan)
        $klaim = $this->resolveKlaim($raw->klasifikasi_klaim);

        // 6. Kategori Media <- Kategori Media
        $kategoriMedia = $this->resolveKategoriMedia($raw->kategori_media);

        // 7. Kategori Iklan <- Media Iklan
        $kategoriIklan = $this->resolveKategoriIklan($raw->media_iklan);

        // 8. Nama Media <- Tempat Pengambilan Iklan
        $namaMedia = $this->resolveNamaMedia($raw->tempat_pengambilan_iklan);

        // 9. Pencantuman NIE <- PENCANTUMAN NOMOR IZIN EDAR
        $pencantumanNie = $this->resolvePencantumanNie($raw->pencantuman_nomor_izin_edar);

        // 10. Kesesuaian NIE <- Kesesuaian Hasil Evaluasi / Pencantuman NIE
        $kesesuaianNie = $this->resolveKesesuaianNie($raw->kesesuaian_hasil_evaluasi, $raw->pencantuman_nomor_izin_edar, $pencantumanNie);

        // 11. Hasil Evaluasi <- Kesimpulan UPT
        $hasilEvaluasi = $this->resolveHasilEvaluasi($raw->kesimpulan_upt, $raw->kesimpulan_pusat);

        // 12. Rincian TMK <- Rincian TMK
        $rincianTmk = $this->resolveRincianTmk($raw->rincian_tmk);

        // 13. Jenis TMK <- Kesimpulan Pusat
        $jenisTmk = $this->resolveJenisTmk($raw->kesimpulan_pusat);

        // 14. Pembuat Iklan <- Pembuat Iklan
        $pembuatIklan = $this->resolvePembuatIklan($raw->pembuat_iklan);

        // 15. Kesesuaian Evaluasi <- Kesesuaian Hasil Evaluasi
        $kesesuaianEvaluasi = $this->resolveKesesuaianEvaluasi($raw->kesesuaian_hasil_evaluasi);

        // Tentukan status_standardisasi (tanpa perlu_koreksi / tanpa input manual untuk kolom kosong SIPT)
        $statusStandardisasi = ($hasilEvaluasi === null && $this->isEmptySipt($raw->kesimpulan_pusat))
            ? 'menunggu_evaluasi'
            : 'terstandar';

        return [
            'upt' => $uptCode,
            'periode_bulan' => $periodeBulan,
            'periode_tahun' => $periodeTahun,
            'jenis_produk' => $jenisProduk,
            'asal_produk' => $asalProduk,
            'klaim' => $klaim,
            'kategori_media' => $kategoriMedia,
            'kategori_iklan' => $kategoriIklan,
            'nama_media' => $namaMedia,
            'pencantuman_nie' => $pencantumanNie,
            'kesesuaian_nie' => $kesesuaianNie,
            'hasil_evaluasi' => $hasilEvaluasi,
            'rincian_tmk' => $rincianTmk,
            'jenis_tmk' => $jenisTmk,
            'pembuat_iklan' => $pembuatIklan,
            'kesesuaian_evaluasi' => $kesesuaianEvaluasi,
            'evaluator_id' => $raw->evaluator_id,
            'status_standardisasi' => $statusStandardisasi,
            'catatan_tidak_terpetakan' => null,
        ];
    }

    
    public function finalizeDataset(Dataset $dataset): array
    {
        $this->syncEvaluatedRows($dataset);

        $evaluatedItems = DataIklan::where('dataset_id', $dataset->id)
            ->where('is_final', true)
            ->orderBy('no', 'asc')
            ->get();

        if ($evaluatedItems->isEmpty()) {
            throw new Exception('Belum ada baris data yang selesai dievaluasi (Kualitas Laporan, Ketepatan Waktu, dan Evaluator harus diisi).');
        }

        $maxPreviousNo = (int) DataIklan::where('periode_tahun', $dataset->periode_tahun)
            ->where('dataset_id', '!=', $dataset->id)
            ->where('is_final', true)
            ->max('no');

        $currentNo = $maxPreviousNo;
        foreach ($evaluatedItems as $item) {
            $currentNo++;
            $item->update(['no' => $currentNo]);
        }

        $dataset->update([
            'status' => 'Final',
            'finalized_at' => now(),
        ]);

        return [
            'start_no' => (int) $evaluatedItems->first()->fresh()->no,
            'end_no' => (int) $evaluatedItems->last()->fresh()->no,
            'total' => $evaluatedItems->count(),
        ];
    }

    public function getUnmappedSummaries(Dataset $dataset): array
    {
        return [];
    }

    public function saveMappingRule(string $kolomSumber, string $nilaiSipt, int|string $kode, string $grup, Dataset $dataset): void
    {
        if ($this->isEmptySipt($nilaiSipt) || $nilaiSipt === '[KOSONG DARI SIPT]') {
            return;
        }

        $normalizedText = strtoupper(trim(preg_replace('/\s+/', ' ', $nilaiSipt)));
        $kodeInt = (string) $kode === '1_2' ? 1 : (int) $kode;

        PemetaanNilai::updateOrCreate(
            [
                'kolom_sumber' => $kolomSumber,
                'nilai_sipt' => $normalizedText,
            ],
            [
                'grup' => $grup,
                'kode' => $kodeInt,
                'is_active' => true,
            ]
        );

        $this->loadCaches(true);
        $this->standardizeDataset($dataset);
    }

    protected function isEmptySipt(?string $val): bool
    {
        if ($val === null) {
            return true;
        }
        $trimmed = trim($val);

        return $trimmed === '' || $trimmed === '-';
    }

    /**
     * 1. UPT <- UPT
     */
    public function resolveUpt(?string $siptUpt): ?int
    {
        if ($this->isEmptySipt($siptUpt)) {
            return null;
        }

        if ($this->cachedUpts === null) {
            $this->loadCaches();
        }

        $mapped = $this->lookupPemetaan('upt', 'UPT', $siptUpt);
        if ($mapped !== null) {
            return $mapped;
        }

        $upt = app(DatasetService::class)->findMatchingUpt($siptUpt, $this->cachedUpts);

        return $upt ? (int) $upt->kode : null;
    }

    /**
     * 2. Periode Bulan <- Tanggal Pengambilan Iklan (1-12 sesuai MasterKode bulan)
     */
    public function resolvePeriodeBulan(?string $tanggal): ?int
    {
        if ($this->isEmptySipt($tanggal)) {
            return null;
        }

        $upper = strtoupper(trim($tanggal));

        if (is_numeric($upper)) {
            $num = (int) $upper;

            return ($num >= 1 && $num <= 12) ? $num : null;
        }

        $monthMap = [
            'JANUARI' => 1, 'JANUARY' => 1, 'JAN' => 1,
            'FEBRUARI' => 2, 'FEBRUARY' => 2, 'FEB' => 2, 'PEBRUARI' => 2,
            'MARET' => 3, 'MARCH' => 3, 'MAR' => 3,
            'APRIL' => 4, 'APR' => 4,
            'MEI' => 5, 'MAY' => 5,
            'JUNI' => 6, 'JUNE' => 6, 'JUN' => 6,
            'JULI' => 7, 'JULY' => 7, 'JUL' => 7,
            'AGUSTUS' => 8, 'AUGUST' => 8, 'AGU' => 8, 'AUG' => 8, 'AGST' => 8,
            'SEPTEMBER' => 9, 'SEPT' => 9, 'SEP' => 9,
            'OKTOBER' => 10, 'OCTOBER' => 10, 'OKT' => 10, 'OCT' => 10,
            'NOVEMBER' => 11, 'NOPEMBER' => 11, 'NOV' => 11, 'NOP' => 11,
            'DESEMBER' => 12, 'DECEMBER' => 12, 'DES' => 12, 'DEC' => 12,
        ];

        foreach ($monthMap as $name => $code) {
            if (preg_match('/\b' . $name . '\b/', $upper)) {
                return $code;
            }
        }

        // Format YYYY-MM-DD
        if (preg_match('/^\d{4}[-\/](\d{1,2})[-\/]\d{1,2}/', $upper, $m)) {
            $month = (int) $m[1];

            return ($month >= 1 && $month <= 12) ? $month : null;
        }

        // Format DD-MM-YYYY atau DD/MM/YYYY
        if (preg_match('/^\d{1,2}[-\/](\d{1,2})[-\/]\d{2,4}/', $upper, $m)) {
            $month = (int) $m[1];

            return ($month >= 1 && $month <= 12) ? $month : null;
        }

        return null;
    }

    public function resolvePeriodeTahun(?string $tanggal): ?int
    {
        if ($this->isEmptySipt($tanggal)) {
            return null;
        }

        if (preg_match('/\b(20\d{2})\b/', $tanggal, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * 3. Jenis Produk <- Jenis Produk (1: OBA/OT, 2: SK, 3: OK)
     */
    public function resolveJenisProduk(?string $val, ?string $komoditi = null, ?string $nie = null): ?int
    {
        if ($this->isEmptySipt($val)) {
            return null;
        }

        $upper = strtoupper(trim($val));

        if ($upper === 'OK' || str_contains($upper, 'OBAT KUASI') || str_contains($upper, 'KUASI') || $upper === '3') {
            return 3;
        }
        if ($upper === 'OT' || $upper === 'OBA' || str_contains($upper, 'OBAT TRADISIONAL') || str_contains($upper, 'OBAT BAHAN ALAM') || str_contains($upper, 'TRADISIONAL') || str_contains($upper, 'BAHAN ALAM') || str_contains($upper, 'JAMU') || str_contains($upper, 'HERBAL') || str_contains($upper, 'FITOFARMAKA') || $upper === '1') {
            return 1;
        }
        if ($upper === 'SK' || str_contains($upper, 'SUPLEMEN KESEHATAN') || str_contains($upper, 'SUPLEMEN') || $upper === '2') {
            return 2;
        }

        return $this->lookupPemetaan('jenis_produk', 'JENIS PRODUK', $val);
    }

    /**
     * 4. Asal Produk <- Asal Produk (1: LOKAL, 2: IMPOR, 3: LISENSI)
     */
    public function resolveAsalProduk(?string $val, ?string $nie = null): ?int
    {
        if ($this->isEmptySipt($val)) {
            return null;
        }

        $upper = strtoupper(trim($val));
        if ($upper === 'LOKAL' || str_contains($upper, 'DALAM NEGERI') || $upper === '1') {
            return 1;
        }
        if ($upper === 'IMPOR' || $upper === 'IMPORT' || str_contains($upper, 'LUAR NEGERI') || $upper === '2') {
            return 2;
        }
        if ($upper === 'LISENSI' || $upper === '3') {
            return 3;
        }

        return $this->lookupPemetaan('asal_produk', 'ASAL PRODUK', $val);
    }

    /**
     * 5. Klaim <- Klasifikasi Iklan / Klasifikasi Klaim (1-25 sesuai MasterKode klaim)
     */
    public function resolveKlaim(?string $val, ?string $namaProduk = null): ?int
    {
        if ($this->isEmptySipt($val)) {
            return null;
        }

        $upper = strtoupper(trim(preg_replace('/\s+/', ' ', $val)));

        if (is_numeric($upper)) {
            $num = (int) $upper;

            return ($num >= 1 && $num <= 25) ? $num : null;
        }

        $mapped = $this->lookupPemetaan('klaim', 'KLASIFIKASI KLAIM', $upper);
        if ($mapped !== null) {
            return $mapped;
        }

        $claims = $this->cachedMasterKodes->get('klaim', collect());
        foreach ($claims as $item) {
            $masterUpper = strtoupper(trim(preg_replace('/\s+/', ' ', $item->nama)));
            if ($upper === $masterUpper || str_starts_with($upper, substr($masterUpper, 0, 35)) || str_contains($upper, $masterUpper)) {
                return (int) $item->kode;
            }
        }

        $cleanTarget = preg_replace('/[^A-Z0-9]/', '', $upper);
        foreach ($claims as $item) {
            $cleanMaster = preg_replace('/[^A-Z0-9]/', '', strtoupper($item->nama));
            if ($cleanTarget !== '' && ($cleanTarget === $cleanMaster || str_contains($cleanTarget, $cleanMaster) || str_contains($cleanMaster, $cleanTarget))) {
                return (int) $item->kode;
            }
        }

        return null;
    }

    /**
     * 6. Kategori Media <- Kategori Media (1: AUDIO, 2: VISUAL, 3: AUDIO VISUAL)
     */
    public function resolveKategoriMedia(?string $val, ?string $mediaIklan = null, ?string $tempatPengambilan = null): ?int
    {
        if ($this->isEmptySipt($val)) {
            return null;
        }

        $upper = strtoupper(trim(preg_replace('/\s+/', ' ', $val)));
        if ($upper === 'AUDIO' || $upper === '1') {
            return 1;
        }
        if ($upper === 'VISUAL' || $upper === '2') {
            return 2;
        }
        if ($upper === 'AUDIO VISUAL' || $upper === 'AUDIOVISUAL' || $upper === 'AUDIO-VISUAL' || $upper === '3') {
            return 3;
        }

        return $this->lookupPemetaan('kategori_media', 'KATEGORI MEDIA', $val);
    }

    /**
     * 7. Kategori Iklan <- Media Iklan (1-8 sesuai MasterKode kategori_iklan)
     */
    public function resolveKategoriIklan(?string $val, ?string $tempatPengambilan = null): ?int
    {
        if ($this->isEmptySipt($val)) {
            return null;
        }

        $upper = strtoupper(trim($val));
        $clean = preg_replace('/[^A-Z0-9]/', '', $upper);

        if (str_contains($clean, 'ECOMMERCE') || str_contains($clean, 'MARKETPLACE') || $clean === '1') {
            return 1;
        }
        if (str_contains($clean, 'WEBSITE') || str_contains($clean, 'SITUS') || $clean === '2') {
            return 2;
        }
        if (str_contains($clean, 'MEDIASOSIAL') || str_contains($clean, 'MEDSOS') || str_contains($clean, 'SOSIALMEDIA') || $clean === '3') {
            return 3;
        }
        if (str_contains($clean, 'INTERNETLAIN') || $clean === '4') {
            return 4;
        }
        if (str_contains($clean, 'TVNASIONAL') || str_contains($clean, 'TELEVISI') || $clean === '5') {
            return 5;
        }
        if (str_contains($clean, 'PENYIARANLOKAL') || $clean === '6') {
            return 6;
        }
        if (str_contains($clean, 'LUARRUANG') || $clean === '7') {
            return 7;
        }
        if (str_contains($clean, 'CETAK') || $clean === '8') {
            return 8;
        }

        return $this->lookupPemetaan('kategori_iklan', 'MEDIA IKLAN', $val);
    }

    /**
     * 8. Nama Media <- Tempat Pengambilan Iklan (1-19 sesuai MasterKode nama_media)
     */
    public function resolveNamaMedia(?string $val, ?int $kategoriIklan = null): ?int
    {
        if ($this->isEmptySipt($val)) {
            return null;
        }

        $upper = strtoupper(trim($val));

        $mapped = $this->lookupPemetaan('nama_media', 'TEMPAT PENGAMBILAN IKLAN', $upper);
        if ($mapped !== null) {
            return $mapped;
        }

        $keywords = [
            'SHOPEE' => 1,
            'TOKOPEDIA' => 2,
            'TIKTOK SHOP' => 3, 'TIKTOKSHOP' => 3,
            'BLIBLI' => 4,
            'LAZADA' => 5,
            'INSTAGRAM' => 6,
            'FACEBOOK MARKETPLACE' => 11, 'MARKETPLACE FACEBOOK' => 11, 'FB MARKETPLACE' => 11,
            'FACEBOOK' => 7,
            'YOUTUBE' => 8,
            'X/TWITTER' => 9, 'TWITTER' => 9, 'X.COM' => 9,
            'TIKTOK' => 10,
            'TELEMEDISIN' => 12, 'HALODOC' => 12, 'ALODOKTER' => 12,
            'RCTI' => 13,
            'SCTV' => 14,
            'TRANS 7' => 15, 'TRANS7' => 15,
            'INDOSIAR' => 16,
            'GTV' => 17,
            'MNC' => 18,
            'TRANS TV' => 19, 'TRANSTV' => 19,
        ];

        foreach ($keywords as $kw => $code) {
            if (str_contains($upper, $kw)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * 9. Pencantuman NIE <- PENCANTUMAN NOMOR IZIN EDAR (1: ADA, 2: TIDAK ADA)
     * Kolom kosong/null dari dataset SIPT yang diupload tetap bernilai null (tidak dipaksakan atau ditebak).
     */
    public function resolvePencantumanNie(?string $pencantumanNomorIzinEdar): ?int
    {
        if ($this->isEmptySipt($pencantumanNomorIzinEdar)) {
            return null;
        }

        $trimmed = trim($pencantumanNomorIzinEdar);
        $upper = strtoupper(trim(preg_replace('/\s+/', ' ', $trimmed)));

        // 1. Cek nilai yang mengindikasikan TIDAK ADA (2)
        // (Periksa pola spesifik lebih dulu agar 'Ada (Tidak Sesuai)' tidak salah terdeteksi sebagai 'Tidak Ada')
        if (
            str_contains($upper, 'TIDAK ADA') ||
            str_contains($upper, 'TIDAK MENCANTUMKAN') ||
            str_starts_with($upper, 'TIDAK') ||
            str_contains($upper, 'BELUM') ||
            str_contains($upper, 'TANPA') ||
            $upper === '2'
        ) {
            return 2; // TIDAK ADA
        }

        // 2. Cek nilai yang mengindikasikan ADA (1)
        if (
            str_contains($upper, 'ADA') ||
            str_contains($upper, 'MENCANTUMKAN') ||
            str_contains($upper, 'CANTUM') ||
            $upper === '1'
        ) {
            return 1; // ADA
        }

        // 3. Cek pemetaan kustom di database jika ada
        $mapped = $this->lookupPemetaan('pencantuman_nie', 'PENCANTUMAN NOMOR IZIN EDAR', $upper);
        if ($mapped !== null) {
            return $mapped;
        }

        return null;
    }

    /**
     * 10. Kesesuaian NIE <- Kesesuaian Hasil Evaluasi / Pencantuman NIE (1: SESUAI, 2: TIDAK SESUAI)
     * Hanya diproses jika Pencantuman NIE bernilai ADA (1).
     * Jika Pencantuman NIE bernilai null (kosong dari SIPT) atau TIDAK ADA (2), maka Kesesuaian NIE juga null.
     */
    public function resolveKesesuaianNie(?string $kesesuaianHasilEvaluasi, ?string $pencantumanNomorIzinEdar = null, ?int $pencantumanNie = null): ?int
    {
        // Jika pencantuman NIE null atau TIDAK ADA (2), maka kesesuaian NIE wajib null
        if ($pencantumanNie !== 1) {
            return null;
        }

        // Prioritas jika pencantuman_nomor_izin_edar secara spesifik menyatakan kesesuaian (misal 'Ada (Tidak Sesuai)' atau 'Ada (Sesuai)')
        if (! $this->isEmptySipt($pencantumanNomorIzinEdar)) {
            $upperP = strtoupper(trim($pencantumanNomorIzinEdar));
            if (str_contains($upperP, 'TIDAK SESUAI')) {
                return 2;
            }
            if (str_contains($upperP, 'SESUAI')) {
                return 1;
            }
        }

        if ($this->isEmptySipt($kesesuaianHasilEvaluasi)) {
            return null;
        }

        $upper = strtoupper(trim($kesesuaianHasilEvaluasi));
        if (str_contains($upper, 'TIDAK SESUAI') || str_starts_with($upper, 'TIDAK') || $upper === '2') {
            return 2;
        }
        if (str_contains($upper, 'SESUAI') || $upper === '1') {
            return 1;
        }

        return $this->lookupPemetaan('kesesuaian_nie', 'KESESUAIAN HASIL EVALUASI', $kesesuaianHasilEvaluasi);
    }

    /**
     * 11. Hasil Evaluasi <- Kesimpulan UPT (1: MK, 2: TMK)
     * (Mendukung fallback ke Kesimpulan Pusat jika Kesimpulan UPT tidak diisi pada pengujian lama)
     */
    public function resolveHasilEvaluasi(?string $kesimpulanUpt, ?string $kesimpulanPusat = null): ?int
    {
        return $this->standardizeHasil($kesimpulanUpt, $kesimpulanPusat);
    }

    /**
     * Standarisasi Hasil Evaluasi (1: MK, 2: TMK).
     * Mengecek kata negatif 'TIDAK' atau 'TMK' lebih dahulu sebelum mengecek 'MEMENUHI'
     * untuk mencegah risiko false-positive (misal "TIDAK MEMENUHI KETENTUAN" terdeteksi sebagai "MEMENUHI KETENTUAN").
     */
    public function standardizeHasil(?string $kesimpulanUpt, ?string $kesimpulanPusat = null): ?int
    {
        $source = ! $this->isEmptySipt($kesimpulanUpt) ? $kesimpulanUpt : $kesimpulanPusat;
        if ($this->isEmptySipt($source)) {
            return null;
        }

        $upper = strtoupper(trim($source));

        // 1. CEK EKSPLISIT KATA 'TIDAK' ATAU 'TMK' LEBIH DULU SEBELUM 'MEMENUHI'
        // Mencegah risiko false-positive: "TIDAK MEMENUHI KETENTUAN" terdeteksi sebagai "MEMENUHI KETENTUAN"
        if (
            str_contains($upper, 'TIDAK') ||
            str_contains($upper, 'TMK') ||
            $upper === '2'
        ) {
            return 2; // TMK (Tidak Memenuhi Ketentuan)
        }

        // 2. Cek kata 'MEMENUHI' atau 'MK' setelah dipastikan bersih dari kata negatif
        if (
            str_contains($upper, 'MEMENUHI') ||
            $upper === 'MK' ||
            $upper === '1'
        ) {
            return 1; // MK (Memenuhi Ketentuan)
        }

        // 3. Cek aturan pemetaan master/kustom jika ada nilai khusus di luar pola standar
        $mapped = $this->lookupPemetaan('hasil_evaluasi', 'KESIMPULAN UPT', $source);
        if ($mapped !== null) {
            return $mapped;
        }

        return null;
    }

    /**
     * Helper pemetaan teks umum ke kode numerik dengan proteksi anti false-positive string pendek.
     */
    public function resolveTextToCode(?string $text, array $negativeKeywords = [], array $positiveKeywords = [], ?string $grup = null, ?string $kolomSumber = null): ?int
    {
        if ($this->isEmptySipt($text)) {
            return null;
        }

        $upper = strtoupper(trim($text));

        if ($grup && $kolomSumber) {
            $mapped = $this->lookupPemetaan($grup, $kolomSumber, $text);
            if ($mapped !== null) {
                return $mapped;
            }
        }

        // Cek eksplisit negatif lebih dahulu
        foreach ($negativeKeywords as $neg => $code) {
            $keyword = is_int($neg) ? (string) $code : (string) $neg;
            $resultCode = is_int($neg) ? 2 : $code;
            if ($upper === $keyword || str_contains($upper, $keyword)) {
                return $resultCode;
            }
        }

        // Cek positif setelah kata negatif tidak ditemukan
        foreach ($positiveKeywords as $pos => $code) {
            $keyword = is_int($pos) ? (string) $code : (string) $pos;
            $resultCode = is_int($pos) ? 1 : $code;
            if ($upper === $keyword || str_contains($upper, $keyword)) {
                return $resultCode;
            }
        }

        return null;
    }

    /**
     * 12. Rincian TMK <- Rincian TMK (1-8 sesuai MasterKode rincian_tmk)
     */
    public function resolveRincianTmk(?string $rincianTmk, ?string $legacySecondArg = null): ?int
    {
        // Support both resolveRincianTmk($raw->rincian_tmk) and legacy 2-arg signature
        $target = ($legacySecondArg !== null && ! $this->isEmptySipt($legacySecondArg))
            ? $legacySecondArg
            : $rincianTmk;

        if ($this->isEmptySipt($target)) {
            return null;
        }

        $upper = strtoupper(trim(preg_replace('/^[-•\s]+/', '', trim($target))));
        if ($upper === '' || $upper === '-') {
            return null;
        }

        if (is_numeric($upper)) {
            $num = (int) $upper;

            return ($num >= 1 && $num <= 8) ? $num : null;
        }

        $mapped = $this->lookupPemetaan('rincian_tmk', 'RINCIAN TMK', $target)
            ?? $this->lookupPemetaan('rincian_tmk', 'RINCIAN TMK', $upper);
        if ($mapped !== null) {
            return $mapped;
        }

        if (str_contains($upper, 'TANPA IZIN EDAR') || $upper === 'TIE' || str_contains($upper, 'TIE')) {
            return 7;
        }
        if (str_contains($upper, 'TESTIMONI')) {
            return 1;
        }
        if (str_contains($upper, 'HADIAH')) {
            return 2;
        }
        if (str_contains($upper, 'KLAIM BERLEBIHAN')) {
            return 3;
        }
        if (str_contains($upper, 'LAIN')) {
            return 8;
        }

        // Exact match against MasterKode rincian_tmk
        $rincianList = $this->cachedMasterKodes->get('rincian_tmk', collect());
        foreach ($rincianList as $item) {
            $masterUpper = strtoupper(trim($item->nama));
            if ($upper === $masterUpper || str_contains($upper, $masterUpper)) {
                return (int) $item->kode;
            }
        }

        return null;
    }

    /**
     * 13. Jenis TMK <- Kesimpulan Pusat (1: KRITIS, 2: MAYOR, 3: MINOR)
     */
    public function resolveJenisTmk(?string $kesimpulanPusat): ?int
    {
        if ($this->isEmptySipt($kesimpulanPusat)) {
            return null;
        }

        $upper = strtoupper(trim($kesimpulanPusat));

        $mapped = $this->lookupPemetaan('jenis_tmk', 'KESIMPULAN PUSAT', $upper);
        if ($mapped !== null) {
            return $mapped;
        }

        if (str_contains($upper, 'CRITICAL') || str_contains($upper, 'KRITIS') || $upper === '1') {
            return 1;
        }
        if (str_contains($upper, 'MAYOR') || str_contains($upper, 'MAJOR') || $upper === '2') {
            return 2;
        }
        if (str_contains($upper, 'MINOR') || $upper === '3') {
            return 3;
        }

        return null;
    }

    /**
     * 14. Pembuat Iklan <- Pembuat Iklan (1: PELAKU USAHA, 2: PERSEORANGAN)
     */
    public function resolvePembuatIklan(?string $val, ?int $kategoriIklan = null, ?string $tempat = null): ?int
    {
        if ($this->isEmptySipt($val)) {
            return null;
        }

        $upper = strtoupper(trim($val));
        if (str_contains($upper, 'PELAKU USAHA') || str_contains($upper, 'PRODUSEN') ||
            str_contains($upper, 'DISTRIBUTOR') || str_contains($upper, 'OFFICIAL') || $upper === '1') {
            return 1;
        }
        if (str_contains($upper, 'PERSEORANGAN') || str_contains($upper, 'PERORANGAN') ||
            str_contains($upper, 'PRIBADI') || str_contains($upper, 'INDIVIDU') || $upper === '2') {
            return 2;
        }

        return $this->lookupPemetaan('pembuat_iklan', 'PEMBUAT IKLAN', $val);
    }

    /**
     * 15. Kesesuaian Evaluasi <- Kesesuaian Hasil Evaluasi (1: SESUAI, 2: TIDAK SESUAI)
     */
    public function resolveKesesuaianEvaluasi(?string $val, ?int $hasilEvaluasi = null, ?string $kesimpulanUpt = null): ?int
    {
        if ($this->isEmptySipt($val)) {
            return null;
        }

        $upper = strtoupper(trim($val));
        if (str_contains($upper, 'TIDAK SESUAI') || str_starts_with($upper, 'TIDAK') || $upper === '2') {
            return 2;
        }
        if (str_contains($upper, 'SESUAI') || $upper === '1') {
            return 1;
        }

        return $this->lookupPemetaan('kesesuaian_evaluasi', 'KESESUAIAN HASIL EVALUASI', $val);
    }

    protected function lookupPemetaan(string $grup, string $kolomSumber, ?string $nilaiSipt): ?int
    {
        if ($this->isEmptySipt($nilaiSipt)) {
            return null;
        }

        if ($this->cachedPemetaan === null) {
            try {
                $this->loadCaches();
            } catch (\Throwable) {
                return null;
            }
        }

        if ($this->cachedPemetaan === null) {
            return null;
        }

        $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $nilaiSipt)));

        $rule = $this->cachedPemetaan->first(function ($p) use ($kolomSumber, $normalized) {
            return $p->is_active &&
                   strcasecmp($p->kolom_sumber, $kolomSumber) === 0 &&
                   strcasecmp($p->nilai_sipt, $normalized) === 0;
        });

        return $rule ? (int) $rule->kode : null;
    }

    protected function loadCaches(bool $force = false): void
    {
        try {
            if ($this->cachedUpts === null || $force) {
                $this->cachedUpts = Upt::all();
            }
            if ($this->cachedMasterKodes === null || $force) {
                $this->cachedMasterKodes = MasterKode::all()->groupBy('grup');
            }
            if ($this->cachedPemetaan === null || $force) {
                $this->cachedPemetaan = PemetaanNilai::where('is_active', true)->get();
            }
        } catch (\Throwable) {
            $this->cachedUpts = $this->cachedUpts ?? collect();
            $this->cachedMasterKodes = $this->cachedMasterKodes ?? collect();
            $this->cachedPemetaan = $this->cachedPemetaan ?? collect();
        }
    }
}
