<?php

namespace App\Services;

use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\DatasetIssue;
use App\Models\Upt;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatasetService
{
    public function __construct(
        protected SiptDatasetReader $reader
    ) {}

    /**
     * Process, validate, and store raw SIPT dataset.
     */
    public function process(Dataset $dataset): void
    {
        $dataset->update(['status' => 'Diproses']);

        try {
            $relativePath = str_starts_with($dataset->path_file, 'private/')
                ? substr($dataset->path_file, strlen('private/'))
                : $dataset->path_file;

            $filePath = \Illuminate\Support\Facades\Storage::disk('private')->path($relativePath);
            $parsed = $this->reader->read($filePath);

            $this->validateAndPersist($dataset, $parsed);
        } catch (Exception $e) {
            $dataset->issues()->create([
                'tingkat' => 'error',
                'pesan' => 'Gagal memproses file: ' . $e->getMessage(),
            ]);

            $dataset->update([
                'status' => 'Gagal',
                'catatan' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate parsed data and persist to data_mentah and dataset_issues.
     */
    public function validateAndPersist(Dataset $dataset, array $parsed): void
    {
        $issues = [];
        $header = $parsed['header'] ?? [];
        $rows = $parsed['rows'] ?? [];

        // 1. Validasi Dataset Final Duplikat
        $existingFinal = Dataset::where('komoditi', $dataset->komoditi)
            ->where('periode_tahun', $dataset->periode_tahun)
            ->where('periode_bulan', $dataset->periode_bulan)
            ->where('status', 'Final')
            ->where('id', '!=', $dataset->id)
            ->first();

        if ($existingFinal) {
            $issues[] = [
                'nomor_baris' => null,
                'kolom' => 'KOMODITI/PERIODE',
                'tingkat' => 'error',
                'pesan' => "Dataset Final untuk komoditi {$dataset->komoditi} periode {$dataset->nama_bulan} {$dataset->periode_tahun} sudah ada di sistem. Upload ditolak.",
            ];
        }

        // 2. Validasi Header Komoditi (Table 1)
        if (! empty($header['komoditi'])) {
            $headerKomoditiNormalized = strtoupper($header['komoditi']);
            $komoditiCocok = match ($dataset->komoditi) {
                'OT' => str_contains($headerKomoditiNormalized, 'OBAT TRADISIONAL') || str_contains($headerKomoditiNormalized, '(OT)'),
                'OK' => str_contains($headerKomoditiNormalized, 'OBAT KUASI') || str_contains($headerKomoditiNormalized, '(OK)'),
                'SK' => str_contains($headerKomoditiNormalized, 'SUPLEMEN KESEHATAN') || str_contains($headerKomoditiNormalized, '(SK)'),
                default => false,
            };

            if (! $komoditiCocok) {
                $issues[] = [
                    'nomor_baris' => null,
                    'kolom' => 'HEADER KOMODITI',
                    'tingkat' => 'error',
                    'pesan' => "Komoditi pada header file ('{$header['komoditi']}') tidak sesuai dengan pilihan input form ({$dataset->nama_komoditi}).",
                ];
            }
        }

        // 3. Validasi Periode Header (Table 1)
        if (! empty($header['periode_mulai'])) {
            $mulaiDate = SiptDatasetReader::parseIndonesianDate($header['periode_mulai']);
            if ($mulaiDate) {
                $dataset->periode_mulai = $mulaiDate->toDateString();
                if ($mulaiDate->year !== (int)$dataset->periode_tahun || $mulaiDate->month !== (int)$dataset->periode_bulan) {
                    $issues[] = [
                        'nomor_baris' => null,
                        'kolom' => 'HEADER PERIODE MULAI',
                        'tingkat' => 'warning',
                        'pesan' => "Periode mulai pada header file ('{$header['periode_mulai']}') berbeda dari bulan/tahun upload ({$dataset->nama_bulan} {$dataset->periode_tahun}).",
                    ];
                }
            }
        }

        if (! empty($header['periode_akhir'])) {
            $akhirDate = SiptDatasetReader::parseIndonesianDate($header['periode_akhir']);
            if ($akhirDate) {
                $dataset->periode_akhir = $akhirDate->toDateString();
            }
        }

        // 4. Validasi Minimal 1 Baris Data
        if (count($rows) === 0) {
            $issues[] = [
                'nomor_baris' => null,
                'kolom' => 'DATA',
                'tingkat' => 'error',
                'pesan' => 'File tidak memiliki baris data atau tabel data tidak ditemukan.',
            ];
        }

        // 5. Validasi Baris Data
        $uptList = Upt::all();
        $seenRowHashes = [];
        $menungguEvaluasiCount = 0;

        foreach ($rows as $index => $row) {
            $nomorBaris = $index + 1;

            // Cek UPT (Catatan info / warning, jangan tolak dataset)
            $siptUpt = $row['upt'] ?? '';
            $uptMatched = $this->findMatchingUpt($siptUpt, $uptList);
            if (! $uptMatched) {
                $issues[] = [
                    'nomor_baris' => $nomorBaris,
                    'kolom' => 'UPT',
                    'tingkat' => 'warning',
                    'pesan' => "Unit Pelaksana Teknis '{$siptUpt}' tidak dikenali dalam Master 83 UPT.",
                ];
            }

            // Cek Keselarasan Jenis Produk terhadap Komoditi
            $siptJenis = strtoupper(trim($row['jenis_produk'] ?? ''));
            $jenisCocok = match ($dataset->komoditi) {
                'OT' => str_contains($siptJenis, 'OBAT TRADISIONAL') || $siptJenis === 'OT' || $siptJenis === '1',
                'OK' => str_contains($siptJenis, 'OBAT KUASI') || $siptJenis === 'OK' || $siptJenis === '3',
                'SK' => str_contains($siptJenis, 'SUPLEMEN KESEHATAN') || $siptJenis === 'SK' || $siptJenis === '2',
                default => true,
            };

            if (! $jenisCocok && $siptJenis !== '') {
                $issues[] = [
                    'nomor_baris' => $nomorBaris,
                    'kolom' => 'JENIS PRODUK',
                    'tingkat' => 'warning',
                    'pesan' => "Jenis Produk '{$row['jenis_produk']}' tidak selaras dengan komoditi dataset ({$dataset->komoditi}).",
                ];
            }

            // Cek Validitas Tanggal Pengambilan Iklan
            $tglPengambilanStr = $row['tanggal_pengambilan_iklan'] ?? '';
            if ($tglPengambilanStr !== '') {
                $tglPengambilan = SiptDatasetReader::parseIndonesianDate($tglPengambilanStr);
                if (! $tglPengambilan) {
                    $issues[] = [
                        'nomor_baris' => $nomorBaris,
                        'kolom' => 'TANGGAL PENGAMBILAN IKLAN',
                        'tingkat' => 'warning',
                        'pesan' => "Format tanggal pengambilan iklan '{$tglPengambilanStr}' berbeda dari format baku SIPT.",
                    ];
                } else {
                    // Warning jika tanggal berada di luar bulan/tahun upload
                    if ($tglPengambilan->year !== (int)$dataset->periode_tahun || $tglPengambilan->month !== (int)$dataset->periode_bulan) {
                        $issues[] = [
                            'nomor_baris' => $nomorBaris,
                            'kolom' => 'TANGGAL PENGAMBILAN IKLAN',
                            'tingkat' => 'warning',
                            'pesan' => "Tanggal pengambilan iklan ({$tglPengambilan->format('d/m/Y')}) berada di luar bulan periode upload ({$dataset->nama_bulan} {$dataset->periode_tahun}).",
                        ];
                    }
                }
            }

            // Cek Baris Identik (Warning)
            $rowHash = md5(implode('|', [
                $row['upt'] ?? '',
                $row['nama_produk'] ?? '',
                $row['nomor_izin_edar'] ?? '',
                $row['tanggal_pengambilan_iklan'] ?? '',
                $row['media_iklan'] ?? '',
                $row['link_url_iklan'] ?? '',
            ]));

            if (isset($seenRowHashes[$rowHash])) {
                $issues[] = [
                    'nomor_baris' => $nomorBaris,
                    'kolom' => 'BARIS IDENTIK',
                    'tingkat' => 'warning',
                    'pesan' => "Baris ini terindikasi identik / duplikat dengan baris {$seenRowHashes[$rowHash]}.",
                ];
            } else {
                $seenRowHashes[$rowHash] = $nomorBaris;
            }

            // Cek Status Menunggu Evaluasi
            $kesimpulanPusat = trim($row['kesimpulan_pusat'] ?? '');
            if ($kesimpulanPusat === '' || $kesimpulanPusat === '-') {
                $menungguEvaluasiCount++;
            }
        }

        // Fatal error hanya jika header komoditi tidak sesuai sama sekali atau baris 0
        $hasFatalError = collect($issues)->contains(function ($issue) {
            return in_array($issue['kolom'] ?? '', ['HEADER KOMODITI', 'KOMODITI/PERIODE', 'DATA'])
                && ($issue['tingkat'] ?? '') === 'error';
        });

        DB::transaction(function () use ($dataset, $issues, $rows, $hasFatalError, $menungguEvaluasiCount) {
            // Hapus issues lama jika ada
            $dataset->issues()->delete();

            // Simpan issues baru
            foreach ($issues as $issue) {
                $dataset->issues()->create($issue);
            }

            // SELALU SIMPAN SELURUH BARIS KE data_mentah
            $dataset->dataMentah()->delete();

            $chunk = [];
            foreach ($rows as $index => $row) {
                $row['dataset_id'] = $dataset->id;
                $row['nomor_baris'] = $index + 1;
                $row['created_at'] = now();
                $row['updated_at'] = now();
                $chunk[] = $row;

                if (count($chunk) >= 500) {
                    DataMentah::insert($chunk);
                    $chunk = [];
                }
            }
            if (! empty($chunk)) {
                DataMentah::insert($chunk);
            }

            $status = $hasFatalError ? 'Ditolak' : 'Valid';

            $dataset->update([
                'status' => $status,
                'jumlah_baris' => count($rows),
                'jumlah_menunggu_evaluasi' => $menungguEvaluasiCount,
            ]);
        });
    }

    /**
     * Match SIPT UPT string to Master UPT model.
     */
    public function findMatchingUpt(?string $siptUpt, $uptList = null): ?Upt
    {
        if (empty($siptUpt)) {
            return null;
        }

        $uptList = $uptList ?? Upt::all();

        // 1. Exact match
        $exact = $uptList->first(fn ($u) => strcasecmp(trim($u->nama), trim($siptUpt)) === 0);
        if ($exact) {
            return $exact;
        }

        // 2. Cleaned location name match
        $cleanedSipt = $this->cleanUptName($siptUpt);

        foreach ($uptList as $upt) {
            $cleanedMaster = $this->cleanUptName($upt->nama);
            if ($cleanedSipt !== '' && ($cleanedSipt === $cleanedMaster || str_contains($cleanedSipt, $cleanedMaster) || str_contains($cleanedMaster, $cleanedSipt))) {
                return $upt;
            }
        }

        return null;
    }

    /**
     * Clean UPT prefix to extract base city/regency name.
     */
    public function cleanUptName(string $nama): string
    {
        $prefixes = [
            'balai besar pom di',
            'balai pom di',
            'loka pom di kota',
            'loka pom di kabupaten',
            'loka pom di kab.',
            'loka pom di kepulauan',
            'loka pom di',
            'kabupaten',
            'kab.',
            'kota',
        ];

        $lower = strtolower(trim($nama));
        foreach ($prefixes as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                $lower = substr($lower, strlen($prefix));
            }
        }

        // Clean punctuation and double spaces
        $lower = preg_replace('/[^a-z0-9\s]/', '', $lower);
        return trim(preg_replace('/\s+/', ' ', $lower));
    }
}
