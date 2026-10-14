<?php

namespace App\Services;

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

    /**
     * Standardize all raw data rows for a dataset.
     */
    public function standardizeDataset(Dataset $dataset): void
    {
        $this->loadCaches();

        $rawRows = $dataset->dataMentah()->orderBy('nomor_baris', 'asc')->get();
        if ($rawRows->isEmpty()) {
            return;
        }

        // Get existing manual values if already entered so they aren't lost upon re-running
        $existingManual = DataIklan::where('dataset_id', $dataset->id)
            ->pluck('kesesuaian_evaluasi', 'data_mentah_id')
            ->toArray();
        $existingKualitas = DataIklan::where('dataset_id', $dataset->id)
            ->pluck('kualitas_laporan', 'data_mentah_id')
            ->toArray();
        $existingKetepatan = DataIklan::where('dataset_id', $dataset->id)
            ->pluck('ketepatan_waktu', 'data_mentah_id')
            ->toArray();

        DB::transaction(function () use ($dataset, $rawRows, $existingManual, $existingKualitas, $existingKetepatan) {
            foreach ($rawRows as $index => $raw) {
                $standardized = $this->standardizeRow($dataset, $raw);

                // Preserve manual values
                $standardized['kesesuaian_evaluasi'] = $existingManual[$raw->id] ?? null;
                $standardized['kualitas_laporan'] = $existingKualitas[$raw->id] ?? null;
                $standardized['ketepatan_waktu'] = $existingKetepatan[$raw->id] ?? null;

                // Temporary sequence NO
                $standardized['no'] = $index + 1;

                DataIklan::updateOrCreate(
                    [
                        'dataset_id' => $dataset->id,
                        'data_mentah_id' => $raw->id,
                    ],
                    $standardized
                );
            }

            // Update dataset status to Terstandardisasi if not Final
            if ($dataset->status !== 'Final') {
                $dataset->update(['status' => 'Terstandardisasi']);
            }
        });
    }

    /**
     * Standardize a single raw SIPT record into numeric codes.
     */
    public function standardizeRow(Dataset $dataset, DataMentah $raw): array
    {
        $unmapped = [];

        // 1. UPT
        $uptCode = $this->resolveUpt($raw->upt);
        if ($uptCode === null) {
            $unmapped['upt'] = $raw->upt;
        }

        // 2. Periode Bulan & Tahun (dari metadata upload)
        $periodeBulan = (int) $dataset->periode_bulan;
        $periodeTahun = (int) $dataset->periode_tahun;

        // 3. Jenis Produk (1: OT, 2: SK, 3: OK)
        $jenisProduk = $this->resolveJenisProduk($raw->jenis_produk, $dataset->komoditi);
        if ($jenisProduk === null) {
            $unmapped['jenis_produk'] = $raw->jenis_produk;
        }

        // 4. Asal Produk (1: Lokal, 2: Impor, 3: Lisensi)
        $asalProduk = $this->resolveAsalProduk($raw->asal_produk);
        if ($asalProduk === null) {
            $unmapped['asal_produk'] = $raw->asal_produk;
        }

        // 5. Klaim (1-25)
        $klaim = $this->resolveKlaim($raw->klasifikasi_klaim ?? $raw->klaim_dalam_promosi_iklan);
        if ($klaim === null) {
            $unmapped['klaim'] = $raw->klasifikasi_klaim ?? $raw->klaim_dalam_promosi_iklan;
        }

        // 6. Kategori Media (1: Audio, 2: Visual, 3: Audio Visual)
        $kategoriMedia = $this->resolveKategoriMedia($raw->kategori_media);
        if ($kategoriMedia === null) {
            $unmapped['kategori_media'] = $raw->kategori_media;
        }

        // 7. Kategori Iklan (1: E-Commerce, 2: Website, 3: Media Sosial, 4: Internet Lain, 5: TV Nasional, 6: Penyiaran Lokal, 7: Luar Ruang, 8: Cetak)
        $kategoriIklan = $this->resolveKategoriIklan($raw->media_iklan);
        if ($kategoriIklan === null) {
            $unmapped['kategori_iklan'] = $raw->media_iklan;
        }

        // 8. Nama Media (1-19, "-" jika kategori iklan Website, Penyiaran Lokal, Luar Ruang, Cetak)
        $namaMedia = $this->resolveNamaMedia($raw->tempat_pengambilan_iklan, $kategoriIklan);
        if ($namaMedia === null && in_array($kategoriIklan, [1, 3, 4, 5])) {
            // Unmapped nama media tercatat tetapi tidak memblokir final
            $unmapped['nama_media'] = $raw->tempat_pengambilan_iklan;
        }

        // 9. Pencantuman NIE & Kesesuaian NIE (Hanya untuk E-Commerce / Kategori 1)
        [$pencantumanNie, $kesesuaianNie, $nieUnmapped] = $this->resolveNie($raw->pencantuman_nomor_izin_edar, $kategoriIklan);
        if ($nieUnmapped) {
            $unmapped['pencantuman_nie'] = $raw->pencantuman_nomor_izin_edar;
        }

        // 10. Hasil Evaluasi (1: MK, 2: TMK, null: Menunggu Evaluasi)
        $hasilEvaluasi = $this->resolveHasilEvaluasi($raw->kesimpulan_pusat);

        // 11. Jenis TMK (1: Kritis, 2: Mayor, 3: Minor, null jika MK)
        $jenisTmk = null;
        if ($hasilEvaluasi === 2) {
            $jenisTmk = $this->resolveJenisTmk($raw->kesimpulan_pusat);
            if ($jenisTmk === null) {
                $unmapped['jenis_tmk'] = $raw->kesimpulan_pusat;
            }
        }

        // 12. Rincian TMK (1-8, null jika MK)
        $rincianTmk = null;
        if ($hasilEvaluasi === 2) {
            $rincianTmk = $this->resolveRincianTmk($raw->kategori_tmk, $raw->rincian_tmk);
            if ($rincianTmk === null) {
                $unmapped['rincian_tmk'] = $raw->kategori_tmk . ' | ' . $raw->rincian_tmk;
            }
        }

        // 13. Pembuat Iklan (1: Pelaku Usaha, 2: Perseorangan)
        $pembuatIklan = $this->resolvePembuatIklan($raw->pembuat_iklan);
        if ($pembuatIklan === null) {
            $unmapped['pembuat_iklan'] = $raw->pembuat_iklan;
        }

        // Tentukan status_standardisasi
        $statusStandardisasi = 'terstandar';
        if ($hasilEvaluasi === null) {
            $statusStandardisasi = 'menunggu_evaluasi';
        } else {
            // Check required fields (nama_media diabaikan dari penentu error blocking)
            $requiredUnmapped = array_diff_key($unmapped, ['nama_media' => true]);
            if (! empty($requiredUnmapped)) {
                $statusStandardisasi = 'perlu_koreksi';
            }
        }

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
            'status_standardisasi' => $statusStandardisasi,
            'catatan_tidak_terpetakan' => ! empty($unmapped) ? $unmapped : null,
        ];
    }

    /**
     * Finalize dataset: ensure all required standardizations and manual inputs are complete,
     * then atomically assign official sequential numbers continuing from last finalized NO of that year.
     */
    public function finalizeDataset(Dataset $dataset): array
    {
        $rows = $dataset->dataMentah()->with('dataset')->get();
        $records = DataIklan::where('dataset_id', $dataset->id)->get();

        if ($records->isEmpty()) {
            throw new Exception("Dataset belum distandardisasi. Jalankan konversi terlebih dahulu.");
        }

        // 1. Check unmapped cells on required columns (nama_media is exempted)
        $unmappedCount = 0;
        $unfilledManualCount = 0;

        foreach ($records as $item) {
            if ($item->hasil_evaluasi !== null) { // baris dievaluasi
                // Check required fields
                if (is_null($item->upt) ||
                    is_null($item->jenis_produk) ||
                    is_null($item->asal_produk) ||
                    is_null($item->klaim) ||
                    is_null($item->kategori_media) ||
                    is_null($item->kategori_iklan) ||
                    is_null($item->pembuat_iklan) ||
                    ($item->hasil_evaluasi === 2 && (is_null($item->rincian_tmk) || is_null($item->jenis_tmk))) ||
                    ($item->kategori_iklan === 1 && is_null($item->pencantuman_nie))) {
                    $unmappedCount++;
                }

                // Check 3 manual columns
                if (! $item->isManualComplete()) {
                    $unfilledManualCount++;
                }
            }
        }

        if ($unmappedCount > 0) {
            throw new Exception("Masih terdapat {$unmappedCount} baris dengan sel belum terpetakan pada kolom wajib. Harap lengkapi pemetaan nilai.");
        }

        if ($unfilledManualCount > 0) {
            throw new Exception("Masih terdapat {$unfilledManualCount} baris dengan 3 kolom manual belum diisi lengkap.");
        }

        // Check if there is already another final dataset for this same (komoditi, tahun, bulan)
        $alreadyFinal = Dataset::where('komoditi', $dataset->komoditi)
            ->where('periode_tahun', $dataset->periode_tahun)
            ->where('periode_bulan', $dataset->periode_bulan)
            ->where('status', 'Final')
            ->where('id', '!=', $dataset->id)
            ->exists();

        if ($alreadyFinal) {
            throw new Exception("Dataset Final untuk komoditi {$dataset->komoditi} periode {$dataset->nama_bulan} {$dataset->periode_tahun} sudah ada di sistem.");
        }

        return DB::transaction(function () use ($dataset) {
            // Find highest official 'no' for this year
            $lastNo = DataIklan::where('periode_tahun', $dataset->periode_tahun)
                ->where('is_final', true)
                ->max('no') ?? 0;

            $items = DataIklan::where('dataset_id', $dataset->id)
                ->orderBy('id', 'asc')
                ->get();

            $currentNo = $lastNo;
            foreach ($items as $item) {
                $currentNo++;
                $item->update([
                    'no' => $currentNo,
                    'is_final' => true,
                ]);
            }

            $dataset->update([
                'status' => 'Final',
                'finalized_at' => now(),
            ]);

            return [
                'start_no' => $lastNo + 1,
                'end_no' => $currentNo,
                'total' => $items->count(),
            ];
        });
    }

    /**
     * Get unique unmapped values for evaluator resolution.
     */
    public function getUnmappedSummaries(Dataset $dataset): array
    {
        $records = DataIklan::where('dataset_id', $dataset->id)
            ->whereNotNull('catatan_tidak_terpetakan')
            ->with('dataMentah')
            ->get();

        $grouped = [];

        foreach ($records as $item) {
            $notes = $item->catatan_tidak_terpetakan ?? [];
            foreach ($notes as $col => $rawValue) {
                if ($rawValue === null || trim((string)$rawValue) === '') {
                    continue;
                }

                $key = $col . '||' . trim((string)$rawValue);
                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'kolom' => $col,
                        'nilai' => trim((string)$rawValue),
                        'jumlah' => 0,
                        'contoh_baris' => $item->no,
                        'contoh_produk' => $item->dataMentah->nama_produk ?? '-',
                    ];
                }
                $grouped[$key]['jumlah']++;
            }
        }

        return array_values($grouped);
    }

    /**
     * Save a mapping rule and re-standardize matching rows.
     */
    public function saveMappingRule(string $kolomSumber, string $nilaiSipt, int $kode, string $grup, Dataset $dataset): void
    {
        $normalizedText = strtoupper(trim(preg_replace('/\s+/', ' ', $nilaiSipt)));

        PemetaanNilai::updateOrCreate(
            [
                'kolom_sumber' => $kolomSumber,
                'nilai_sipt' => $normalizedText,
            ],
            [
                'grup' => $grup,
                'kode' => $kode,
                'is_active' => true,
            ]
        );

        // Re-standardize dataset immediately
        $this->loadCaches(true);
        $this->standardizeDataset($dataset);
    }

    // ==========================================
    // RESOLVERS
    // ==========================================

    public function resolveUpt(?string $siptUpt): ?int
    {
        if (empty($siptUpt)) return null;

        $upt = app(DatasetService::class)->findMatchingUpt($siptUpt, $this->cachedUpts);
        return $upt ? $upt->kode : null;
    }

    public function resolveJenisProduk(?string $val, string $komoditi): ?int
    {
        if (empty($val)) {
            return match ($komoditi) {
                'OT' => 1,
                'SK' => 2,
                'OK' => 3,
                default => null,
            };
        }

        $upper = strtoupper(trim($val));
        if ($upper === 'OT' || str_contains($upper, 'OBAT TRADISIONAL') || $upper === '1') return 1;
        if ($upper === 'SK' || str_contains($upper, 'SUPLEMEN KESEHATAN') || $upper === '2') return 2;
        if ($upper === 'OK' || str_contains($upper, 'OBAT KUASI') || $upper === '3') return 3;

        return $this->lookupPemetaan('jenis_produk', 'JENIS PRODUK', $val);
    }

    public function resolveAsalProduk(?string $val): ?int
    {
        if (empty($val)) return null;

        $upper = strtoupper(trim($val));
        if ($upper === 'LOKAL' || str_contains($upper, 'DALAM NEGERI') || $upper === '1') return 1;
        if ($upper === 'IMPOR' || $upper === 'IMPORT' || $upper === '2') return 2;
        if ($upper === 'LISENSI' || $upper === '3') return 3;

        return $this->lookupPemetaan('asal_produk', 'ASAL PRODUK', $val);
    }

    public function resolveKlaim(?string $val): ?int
    {
        if (empty($val)) return null;

        $upper = strtoupper(trim(preg_replace('/\s+/', ' ', $val)));

        // Look up in pemetaan_nilai
        $mapped = $this->lookupPemetaan('klaim', 'KLASIFIKASI KLAIM', $upper);
        if ($mapped !== null) return $mapped;

        // Try matching with 25 master claims
        $claims = $this->cachedMasterKodes->get('klaim', collect());
        foreach ($claims as $item) {
            $masterUpper = strtoupper(trim(preg_replace('/\s+/', ' ', $item->nama)));
            if ($upper === $masterUpper || str_starts_with($upper, substr($masterUpper, 0, 40))) {
                return $item->kode;
            }
        }

        return null;
    }

    public function resolveKategoriMedia(?string $val): ?int
    {
        if (empty($val)) return null;

        $upper = strtoupper(trim($val));
        if ($upper === 'AUDIO' || $upper === '1') return 1;
        if ($upper === 'VISUAL' || $upper === '2') return 2;
        if ($upper === 'AUDIO VISUAL' || $upper === 'AUDIOVISUAL' || $upper === '3') return 3;

        return $this->lookupPemetaan('kategori_media', 'KATEGORI MEDIA', $val);
    }

    public function resolveKategoriIklan(?string $val): ?int
    {
        if (empty($val)) return null;

        $upper = strtoupper(trim($val));
        if (str_contains($upper, 'ECOMMERCE') || str_contains($upper, 'E-COMMERCE') || $upper === '1') return 1;
        if (str_contains($upper, 'WEBSITE') || str_contains($upper, 'SITUS') || $upper === '2') return 2;
        if (str_contains($upper, 'MEDIA SOSIAL') || str_contains($upper, 'MEDSOS') || $upper === '3') return 3;
        if (str_contains($upper, 'INTERNET LAIN') || $upper === '4') return 4;
        if (str_contains($upper, 'TV NASIONAL') || str_contains($upper, 'TELEVISI') || $upper === '5') return 5;
        if (str_contains($upper, 'PENYIARAN LOKAL') || $upper === '6') return 6;
        if (str_contains($upper, 'LUAR RUANG') || $upper === '7') return 7;
        if (str_contains($upper, 'CETAK') || $upper === '8') return 8;

        return $this->lookupPemetaan('kategori_iklan', 'MEDIA IKLAN', $val);
    }

    public function resolveNamaMedia(?string $val, ?int $kategoriIklan): ?int
    {
        // Untuk Website, Penyiaran Lokal, Luar Ruang, Cetak: tidak berlaku ("-")
        if (in_array($kategoriIklan, [2, 6, 7, 8])) {
            return null;
        }

        if (empty($val)) return null;

        $upper = strtoupper(trim($val));

        $mapped = $this->lookupPemetaan('nama_media', 'TEMPAT PENGAMBILAN IKLAN', $upper);
        if ($mapped !== null) return $mapped;

        // Keyword matches
        $keywords = [
            'SHOPEE' => 1,
            'TOKOPEDIA' => 2,
            'TIKTOK SHOP' => 3, 'TIKTOKSHOP' => 3,
            'BLIBLI' => 4,
            'LAZADA' => 5,
            'INSTAGRAM' => 6,
            'FACEBOOK MARKETPLACE' => 11,
            'FACEBOOK' => 7,
            'YOUTUBE' => 8,
            'TWITTER' => 9, 'X.COM' => 9,
            'TIKTOK' => 10,
            'HALODOC' => 12, 'ALODOKTER' => 12, 'TELEMEDISIN' => 12,
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

    public function resolveNie(?string $val, ?int $kategoriIklan): array
    {
        // Media selain E-Commerce: '-' pada keduanya
        if ($kategoriIklan !== 1) {
            return [null, null, false];
        }

        if (empty($val)) {
            return [null, null, true];
        }

        $lower = strtolower(trim($val));

        // awalan "ada (sesuai)" = 1 dan 1
        if (str_starts_with($lower, 'ada (sesuai') || str_starts_with($lower, 'ada ( sesuai')) {
            return [1, 1, false];
        }

        // awalan "ada (tidak" = 1 dan 2
        if (str_starts_with($lower, 'ada (tidak') || str_starts_with($lower, 'ada ( tidak')) {
            return [1, 2, false];
        }

        // awalan "tidak" = 2 dan "-"
        if (str_starts_with($lower, 'tidak')) {
            return [2, null, false];
        }

        // Check pemetaan
        $mapped = $this->lookupPemetaan('pencantuman_nie', 'PENCANTUMAN NOMOR IZIN EDAR', $val);
        if ($mapped !== null) {
            return [$mapped, $mapped === 1 ? 1 : null, false];
        }

        return [null, null, true];
    }

    public function resolveHasilEvaluasi(?string $val): ?int
    {
        if (empty($val) || trim($val) === '' || trim($val) === '-') {
            return null; // Baris menunggu evaluasi
        }

        $upper = strtoupper(trim($val));
        if (str_contains($upper, 'MEMENUHI KETENTUAN') && ! str_contains($upper, 'TIDAK') && ! str_contains($upper, 'TMK')) {
            return 1; // MK
        }

        if (str_starts_with($upper, 'TMK') || str_contains($upper, 'TIDAK MEMENUHI KETENTUAN')) {
            return 2; // TMK
        }

        return null;
    }

    public function resolveJenisTmk(?string $kesimpulanPusat): ?int
    {
        if (empty($kesimpulanPusat)) return null;

        $upper = strtoupper(trim($kesimpulanPusat));
        if (str_contains($upper, 'CRITICAL') || str_contains($upper, 'KRITIS')) return 1;
        if (str_contains($upper, 'MAYOR') || str_contains($upper, 'MAJOR')) return 2;
        if (str_contains($upper, 'MINOR')) return 3;

        return null;
    }

    public function resolveRincianTmk(?string $kategoriTmk, ?string $rincianTmk): ?int
    {
        // Bila KATEGORI TMK = "Produk Tanpa Izin Edar" maka 7 (TIE)
        if (! empty($kategoriTmk)) {
            $upperKat = strtoupper(trim($kategoriTmk));
            if (str_contains($upperKat, 'PRODUK TANPA IZIN EDAR') || str_contains($upperKat, 'TIE')) {
                return 7;
            }
        }

        if (empty($rincianTmk)) return null;

        $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $rincianTmk)));

        // Look up in pemetaan_nilai
        $mapped = $this->lookupPemetaan('rincian_tmk', 'RINCIAN TMK', $normalized);
        if ($mapped !== null) return $mapped;

        // Keyword rules
        if (str_contains($normalized, 'TESTIMONI')) return 1;
        if (str_contains($normalized, 'HADIAH')) return 2;
        if (str_contains($normalized, 'KLAIM BERLEBIHAN')) return 3;
        if (str_contains($normalized, 'LAIN')) return 8;

        return null;
    }

    public function resolvePembuatIklan(?string $val): ?int
    {
        if (empty($val) || trim($val) === '-') return null;

        $upper = strtoupper(trim($val));
        if (str_contains($upper, 'PELAKU USAHA') || str_contains($upper, 'PRODUSEN') || $upper === '1') return 1;
        if (str_contains($upper, 'PERSEORANGAN') || str_contains($upper, 'PERORANGAN') || $upper === '2') return 2;

        return $this->lookupPemetaan('pembuat_iklan', 'PEMBUAT IKLAN', $val);
    }

    protected function lookupPemetaan(string $grup, string $kolomSumber, ?string $nilaiSipt): ?int
    {
        if (empty($nilaiSipt)) return null;

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
        if ($this->cachedUpts === null || $force) {
            $this->cachedUpts = Upt::all();
        }
        if ($this->cachedMasterKodes === null || $force) {
            $this->cachedMasterKodes = MasterKode::all()->groupBy('grup');
        }
        if ($this->cachedPemetaan === null || $force) {
            $this->cachedPemetaan = PemetaanNilai::where('is_active', true)->get();
        }
    }
}
