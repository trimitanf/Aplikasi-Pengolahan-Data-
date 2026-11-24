<?php

namespace App\Services\Iklan;

use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\Upt;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

            $filePath = Storage::disk('private')->path($relativePath);
            $parsed = $this->reader->read($filePath);

            $this->validateAndPersist($dataset, $parsed);
        } catch (\Throwable $e) {
            $pesan = $e instanceof \Illuminate\Database\QueryException
                ? 'Gagal menyimpan data ke basis data: ' . ($e->errorInfo[2] ?? $e->getMessage())
                : 'Gagal memproses file: ' . $e->getMessage();

            $pesanClean = \Illuminate\Support\Str::limit($pesan, 5000);

            try {
                $dataset->issues()->create([
                    'tingkat' => 'error',
                    'pesan' => $pesanClean,
                ]);

                $dataset->update([
                    'status' => 'Gagal',
                    'catatan' => $pesanClean,
                ]);
            } catch (\Throwable $loggingError) {
                \Illuminate\Support\Facades\Log::error('Gagal mencatat dataset issue: ' . $loggingError->getMessage());
            }
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

        // 1. Ekstrak Rentang Tanggal dari Header SIPT (Table 1)
        if (! empty($header['periode_mulai'])) {
            $mulaiDate = SiptDatasetReader::parseIndonesianDate($header['periode_mulai']);
            if ($mulaiDate) {
                $dataset->periode_mulai = $mulaiDate->toDateString();
            }
        }

        if (! empty($header['periode_akhir'])) {
            $akhirDate = SiptDatasetReader::parseIndonesianDate($header['periode_akhir']);
            if ($akhirDate) {
                $dataset->periode_akhir = $akhirDate->toDateString();
            }
        }

        // Fallback: Jika header tidak memuat rentang tanggal, ekstrak min & max tanggal dari baris data
        if (! $dataset->periode_mulai || ! $dataset->periode_akhir) {
            $rowDates = [];
            foreach ($rows as $r) {
                if (! empty($r['tanggal_pengambilan_iklan'])) {
                    $parsedD = SiptDatasetReader::parseIndonesianDate($r['tanggal_pengambilan_iklan']);
                    if ($parsedD) {
                        $rowDates[] = $parsedD->toDateString();
                    }
                }
            }
            if (! empty($rowDates)) {
                sort($rowDates);
                if (! $dataset->periode_mulai) {
                    $dataset->periode_mulai = $rowDates[0];
                }
                if (! $dataset->periode_akhir) {
                    $dataset->periode_akhir = end($rowDates);
                }
            }
        }

        // 2. Validasi Duplikasi Berdasarkan Tanggal Periode SIPT & Komoditi (Status Final)
        // Penentu duplikasi: Tanggal/rentang tanggal periode SIPT (bukan bulan).
        // Dalam 1 bulan yang sama, diperbolehkan banyak dataset diunggah secara berturut-turut/bertahap,
        // asalkan rentang tanggalnya tidak persis sama dengan dataset yang sudah Final.
        $existingDuplicate = Dataset::where(function ($q) use ($dataset) {
                if (in_array($dataset->komoditi, ['OBA', 'OT'])) {
                    $q->whereIn('komoditi', ['OBA', 'OT']);
                } else {
                    $q->where('komoditi', $dataset->komoditi);
                }
            })
            ->where('uploaded_by', $dataset->uploaded_by)
            ->where('status', 'Final')
            ->where('id', '!=', $dataset->id)
            ->where(function ($q) use ($dataset) {
                if ($dataset->periode_mulai && $dataset->periode_akhir) {
                    $q->where('periode_mulai', $dataset->periode_mulai)
                      ->where('periode_akhir', $dataset->periode_akhir);
                } else {
                    $q->where('nama_file_asli', $dataset->nama_file_asli)
                      ->where('periode_tahun', $dataset->periode_tahun)
                      ->where('periode_bulan', $dataset->periode_bulan);
                }
            })
            ->first();

        if ($existingDuplicate) {
            if ($dataset->periode_mulai && $dataset->periode_akhir) {
                $mulaiTgl = \Carbon\Carbon::parse($dataset->periode_mulai)->translatedFormat('d F Y');
                $akhirTgl = \Carbon\Carbon::parse($dataset->periode_akhir)->translatedFormat('d F Y');
                $rentangTglStr = ($dataset->periode_mulai === $dataset->periode_akhir)
                    ? $mulaiTgl
                    : "{$mulaiTgl} s/d {$akhirTgl}";

                $pesanDuplikat = "Dataset Final untuk komoditi {$dataset->komoditi} dengan rentang tanggal {$rentangTglStr} sudah ada di sistem. Upload ditolak untuk mencegah duplikasi data.";
            } else {
                $pesanDuplikat = "Berkas '{$dataset->nama_file_asli}' untuk komoditi {$dataset->komoditi} periode {$dataset->nama_bulan} {$dataset->periode_tahun} sudah pernah diunggah dan berstatus Final. Upload ditolak untuk mencegah duplikasi data.";
            }

            $issues[] = [
                'nomor_baris' => null,
                'kolom' => 'KOMODITI/PERIODE',
                'tingkat' => 'error',
                'pesan' => $pesanDuplikat,
            ];
        }

        // 3. Validasi Header Komoditi (Table 1)
        // Catatan: OBA adalah Obat Bahan Alam (sebelumnya Obat Tradisional / OT); header SIPT bertuliskan OT atau OBA diakui tanpa warning/error
        if (! empty($header['komoditi'])) {
            $headerKomoditiNormalized = strtoupper(trim($header['komoditi']));
            $komoditiCocok = match ($dataset->komoditi) {
                'OBA', 'OT' => str_contains($headerKomoditiNormalized, 'OBAT BAHAN ALAM')
                    || str_contains($headerKomoditiNormalized, 'OBAT TRADISIONAL')
                    || str_contains($headerKomoditiNormalized, 'OBAT KUASI')
                    || str_contains($headerKomoditiNormalized, 'BAHAN ALAM')
                    || str_contains($headerKomoditiNormalized, 'TRADISIONAL')
                    || str_contains($headerKomoditiNormalized, 'KUASI')
                    || str_contains($headerKomoditiNormalized, '(OBA)')
                    || str_contains($headerKomoditiNormalized, '(OT)')
                    || str_contains($headerKomoditiNormalized, '(OK)')
                    || (bool) preg_match('/\b(OBA|OT|OK)\b/', $headerKomoditiNormalized),
                'OK' => str_contains($headerKomoditiNormalized, 'OBAT KUASI')
                    || str_contains($headerKomoditiNormalized, 'KUASI')
                    || str_contains($headerKomoditiNormalized, '(OK)')
                    || (bool) preg_match('/\bOK\b/', $headerKomoditiNormalized)
                    || str_contains($headerKomoditiNormalized, 'OBAT BAHAN ALAM'),
                'SK' => str_contains($headerKomoditiNormalized, 'SUPLEMEN KESEHATAN')
                    || str_contains($headerKomoditiNormalized, 'SUPLEMEN')
                    || str_contains($headerKomoditiNormalized, '(SK)')
                    || (bool) preg_match('/\b(SK)\b/', $headerKomoditiNormalized),
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
                    'pesan' => "Unit Pelaksana Teknis '{$siptUpt}' belum terdaftar dalam Master Data UPT.",
                ];
            }

            // Cek Keselarasan Jenis Produk terhadap Komoditi (OBA/OT, OK, SK)
            $siptJenis = strtoupper(trim($row['jenis_produk'] ?? ''));
            $nie = strtoupper(preg_replace('/[^A-Z]/', '', substr(trim($row['nomor_izin_edar'] ?? ''), 0, 4)));
            $isNieOt = str_starts_with($nie, 'TR') || str_starts_with($nie, 'TI') ||
                       str_starts_with($nie, 'TL') || str_starts_with($nie, 'HT') ||
                       str_starts_with($nie, 'FF');
            $isNieOk = str_starts_with($nie, 'QD') || str_starts_with($nie, 'QI') ||
                       str_starts_with($nie, 'QL');
            $isNieOba = $isNieOt || $isNieOk;
            $isNieSk = str_starts_with($nie, 'SD') || str_starts_with($nie, 'SI') ||
                       str_starts_with($nie, 'SL');

            $jenisCocok = match ($dataset->komoditi) {
                'OBA', 'OT' => str_contains($siptJenis, 'OBAT BAHAN ALAM')
                    || str_contains($siptJenis, 'BAHAN ALAM')
                    || str_contains($siptJenis, 'OBAT TRADISIONAL')
                    || str_contains($siptJenis, 'OBAT KUASI')
                    || str_contains($siptJenis, 'TRADISIONAL')
                    || str_contains($siptJenis, 'KUASI')
                    || str_contains($siptJenis, 'JAMU')
                    || str_contains($siptJenis, 'HERBAL')
                    || str_contains($siptJenis, 'FITOFARMAKA')
                    || str_contains($siptJenis, 'OBAT TRADISIONAL (OT)')
                    || str_contains($siptJenis, 'OBAT BAHAN ALAM (OBA)')
                    || str_contains($siptJenis, '(OT)')
                    || str_contains($siptJenis, '(OK)')
                    || str_contains($siptJenis, '(OBA)')
                    || in_array($siptJenis, ['OBA', 'OT', 'OK', '1', '3'])
                    || $isNieOt
                    || $isNieOk
                    || $isNieOba,
                'OK' => str_contains($siptJenis, 'OBAT KUASI')
                    || str_contains($siptJenis, 'OBAT BAHAN ALAM')
                    || str_contains($siptJenis, 'BAHAN ALAM')
                    || str_contains($siptJenis, 'KUASI')
                    || in_array($siptJenis, ['OK', 'OBA', '3'])
                    || $isNieOk,
                'SK' => str_contains($siptJenis, 'SUPLEMEN KESEHATAN')
                    || str_contains($siptJenis, 'SUPLEMEN')
                    || in_array($siptJenis, ['SK', '2'])
                    || $isNieSk,
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

            // Cek Validitas Format Tanggal Pengambilan Iklan
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
            // Saat awal diunggah, evaluator_id bernilai null sampai masing-masing evaluator (1 s.d. 6)
            // mengevaluasi baris sesuai pembagian UPT tugasnya.
            $dataset->dataMentah()->delete();

            $chunk = [];
            foreach ($rows as $index => $row) {
                $kesimpulanPusatRow = trim($row['kesimpulan_pusat'] ?? '');
                $isMenungguRow = ($kesimpulanPusatRow === '' || $kesimpulanPusatRow === '-');
                $rawKesesuaian = strtoupper(trim($row['kesesuaian_hasil_evaluasi'] ?? ''));

                $autoKesesuaian = null;
                if (! $isMenungguRow) {
                    if (str_contains($rawKesesuaian, 'TIDAK') || $rawKesesuaian === '2') {
                        $autoKesesuaian = 2;
                    } elseif (str_contains($rawKesesuaian, 'SESUAI') || $rawKesesuaian === '1') {
                        $autoKesesuaian = 1;
                    }
                }

                $row['dataset_id'] = $dataset->id;
                $row['nomor_baris'] = $index + 1;
                $row['no'] = (isset($row['no']) && trim((string) $row['no']) !== '')
                    ? trim((string) $row['no'])
                    : (string) ($index + 1);
                $row['evaluator_id'] = null;
                $row['kesesuaian_evaluasi'] = $autoKesesuaian;
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
        $noSpaceSipt = str_replace(' ', '', $cleanedSipt);

        foreach ($uptList as $upt) {
            $cleanedMaster = $this->cleanUptName($upt->nama);
            $noSpaceMaster = str_replace(' ', '', $cleanedMaster);
            if ($cleanedSipt !== '' && (
                $cleanedSipt === $cleanedMaster ||
                $noSpaceSipt === $noSpaceMaster ||
                str_contains($cleanedSipt, $cleanedMaster) ||
                str_contains($cleanedMaster, $cleanedSipt) ||
                ($noSpaceMaster !== '' && (str_contains($noSpaceSipt, $noSpaceMaster) || str_contains($noSpaceMaster, $noSpaceSipt)))
            )) {
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

    /**
     * Hapus permanen (force delete) dataset beserta file fisik Excel dan data di database.
     *
     * Sebelum data dataset dihapus dari database MySQL, file Excel fisik yang tersimpan
     * di storage/app dihapus menggunakan Storage::disk('local')->delete($dataset->file_path).
     * Dilengkapi penanganan try-catch agar jika file fisiknya sudah terlanjur hilang,
     * proses hapus database tetap berjalan lancar tanpa error.
     */
    public function forceDelete(Dataset|int $dataset): bool
    {
        if (is_int($dataset)) {
            $dataset = Dataset::withTrashed()->findOrFail($dataset);
        }

        // 1. Hapus file Excel fisik yang tersimpan di storage/app sebelum data di database MySQL dihapus
        try {
            if (! empty($dataset->file_path)) {
                Storage::disk('local')->delete($dataset->file_path);
            }

            if (! empty($dataset->path_file) && $dataset->path_file !== ($dataset->file_path ?? null)) {
                Storage::disk('local')->delete($dataset->path_file);
            }

            // Hapus juga via deletePhysicalFile() untuk memastikan pembersihan disk private/local dan direktori storage/app
            if (method_exists($dataset, 'deletePhysicalFile')) {
                $dataset->deletePhysicalFile();
            }
        } catch (\Throwable $e) {
            // Penanganan try-catch agar jika file fisiknya sudah terlanjur hilang atau terjadi error I/O,
            // proses penghapusan di database tetap berjalan lancar tanpa error
            try {
                Log::warning("Gagal menghapus file fisik dataset (ID: {$dataset->id}): " . $e->getMessage());
            } catch (\Throwable) {
                // Abaikan jika logging tidak dapat menulis berkas
            }
        }

        // 2. Hapus data dataset dari database MySQL secara permanen
        return (bool) $dataset->forceDelete();
    }
}
