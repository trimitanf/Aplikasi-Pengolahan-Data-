<?php

namespace App\Console\Commands;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MuatDataAllCommand extends Command
{
    protected $signature = 'iklan:muat-data-all {file? : Path ke file Excel Profil_Data_Iklan.xlsx atau Rekap_dengan_penjelasan.xlsx}';

    protected $description = 'Memuat sheet Data All (5.133 baris berkode) sebagai data final untuk verifikasi rekap';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! $filePath) {
            $defaultPaths = [
                base_path('docs/Profil_Data_Iklan.xlsx'),
                base_path('docs/Rekap_dengan_penjelasan.xlsx'),
            ];

            foreach ($defaultPaths as $path) {
                if (file_exists($path)) {
                    $filePath = $path;
                    break;
                }
            }
        }

        if (! $filePath || ! file_exists($filePath)) {
            $this->error('File tidak ditemukan pada path: '.($filePath ?? 'docs/Profil_Data_Iklan.xlsx'));
            $this->info('Silakan tentukan path file yang valid: php artisan iklan:muat-data-all {path_file}');

            return Command::FAILURE;
        }

        $this->info("Membaca file Excel: {$filePath}...");

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);

            // Cari sheet 'Data All' atau 'data all'
            $sheetNames = $reader->listWorksheetNames($filePath);
            $targetSheet = null;
            foreach ($sheetNames as $name) {
                if (strcasecmp(trim($name), 'Data All') === 0) {
                    $targetSheet = $name;
                    break;
                }
            }

            if (! $targetSheet) {
                $this->error("Sheet 'Data All' tidak ditemukan di dalam file. Sheet yang ada: ".implode(', ', $sheetNames));

                return Command::FAILURE;
            }

            $reader->setLoadSheetsOnly([$targetSheet]);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);

            if (empty($rows)) {
                $this->error("Sheet '{$targetSheet}' kosong.");

                return Command::FAILURE;
            }

            // Baris 1: Header
            $headers = array_shift($rows);
            $this->info('Menemukan '.count($rows)." baris data pada sheet '{$targetSheet}'. Memproses penyimpanan ke database...");

            DB::transaction(function () use ($rows, $filePath) {
                // Buat Dataset benchmark untuk OBA dan SK
                $dataset = Dataset::firstOrCreate(
                    [
                        'nama_file_asli' => basename($filePath),
                        'komoditi' => 'OBA',
                        'periode_tahun' => 2026,
                        'periode_bulan' => 7,
                    ],
                    [
                        'path_file' => 'docs/'.basename($filePath),
                        'status' => 'Final',
                        'jumlah_baris' => count($rows),
                        'uploaded_at' => now(),
                        'finalized_at' => now(),
                    ]
                );

                // Bersihkan data lama jika ada
                DataIklan::where('dataset_id', $dataset->id)->delete();
                DataMentah::where('dataset_id', $dataset->id)->delete();

                $chunkIklan = [];
                $chunkMentah = [];

                foreach ($rows as $index => $row) {
                    $no = is_numeric($row[0] ?? null) ? (int) $row[0] : ($index + 1);
                    $upt = is_numeric($row[1] ?? null) ? (int) $row[1] : null;
                    $periodeBulan = is_numeric($row[2] ?? null) ? (int) $row[2] : 1;
                    $jenisProduk = is_numeric($row[3] ?? null) ? (int) $row[3] : null;
                    $asalProduk = is_numeric($row[4] ?? null) ? (int) $row[4] : null;
                    $klaim = is_numeric($row[5] ?? null) ? (int) $row[5] : null;
                    $kategoriMedia = is_numeric($row[6] ?? null) ? (int) $row[6] : null;
                    $kategoriIklan = is_numeric($row[7] ?? null) ? (int) $row[7] : null;
                    $namaMedia = is_numeric($row[8] ?? null) ? (int) $row[8] : null;
                    $pencantumanNie = is_numeric($row[9] ?? null) ? (int) $row[9] : null;
                    $kesesuaianNie = is_numeric($row[10] ?? null) ? (int) $row[10] : null;
                    $hasilEvaluasi = is_numeric($row[11] ?? null) ? (int) $row[11] : null;
                    $rincianTmk = is_numeric($row[12] ?? null) ? (int) $row[12] : null;
                    $jenisTmk = is_numeric($row[13] ?? null) ? (int) $row[13] : null;
                    $pembuatIklan = is_numeric($row[14] ?? null) ? (int) $row[14] : null;
                    $kesesuaianEvaluasi = is_numeric($row[15] ?? null) ? (int) $row[15] : null;
                    $kualitasLaporan = is_numeric($row[16] ?? null) ? (int) $row[16] : null;
                    $ketepatanWaktu = is_numeric($row[17] ?? null) ? (int) $row[17] : null;

                    // Siapkan DataMentah
                    $dataMentah = DataMentah::create([
                        'dataset_id' => $dataset->id,
                        'nomor_baris' => $no,
                        'no' => (string) $no,
                        'upt' => 'UPT '.$upt,
                        'jenis_produk' => (string) $jenisProduk,
                    ]);

                    $chunkIklan[] = [
                        'dataset_id' => $dataset->id,
                        'data_mentah_id' => $dataMentah->id,
                        'no' => $no,
                        'upt' => $upt,
                        'periode_bulan' => $periodeBulan,
                        'periode_tahun' => 2026,
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
                        'kualitas_laporan' => $kualitasLaporan,
                        'ketepatan_waktu' => $ketepatanWaktu,
                        'status_standardisasi' => $hasilEvaluasi === null ? 'menunggu_evaluasi' : 'terstandar',
                        'is_final' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($chunkIklan) >= 500) {
                        DataIklan::insert($chunkIklan);
                        $chunkIklan = [];
                    }
                }

                if (! empty($chunkIklan)) {
                    DataIklan::insert($chunkIklan);
                }
            });

            $this->info('Berhasil memuat '.count($rows).' baris data ke tabel data_iklan sebagai data final!');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Terjadi kesalahan saat membaca file: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
