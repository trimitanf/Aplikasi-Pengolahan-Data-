<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanupDatasetStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dataset:cleanup-storage
                            {--dry-run : Jalankan simulasi pemindaian tanpa menghapus file fisik}
                            {--keep-trashed : Jangan hapus file yang masih berada di Kotak Sampah (soft-deleted)}
                            {--force : Jalankan pembersihan langsung tanpa konfirmasi interaktif}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memindai dan menghapus berkas fisik dataset (Excel/SIPT) di storage/app yang sudah tidak memiliki relasi atau catatan aktif di database';

    /**
     * Ekstensi berkas dataset yang dipindai.
     *
     * @var array<string>
     */
    protected array $allowedExtensions = ['xlsx', 'xls', 'csv', 'html', 'htm'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $keepTrashed = $this->option('keep-trashed');
        $force = $this->option('force');

        $this->info('=== Pemindaian File Yatim Piatu (Orphaned Files) Dataset ===');
        $this->line('Memeriksa direktori storage dataset dan mencocokkannya dengan tabel database...');

        // 1. Ambil seluruh nama berkas aktif dan berkas sampah dari basis data
        $activeBasenames = Dataset::pluck('path_file')
            ->filter()
            ->map(fn ($p) => basename(str_replace('\\', '/', (string) $p)))
            ->flip()
            ->toArray();

        $trashedBasenames = Dataset::onlyTrashed()
            ->pluck('path_file')
            ->filter()
            ->map(fn ($p) => basename(str_replace('\\', '/', (string) $p)))
            ->flip()
            ->toArray();

        // 2. Daftar direktori target pemindaian di storage/app
        $scanDirectories = [
            storage_path('app/private/datasets'),
            storage_path('app/datasets'),
        ];

        $scannedFiles = [];
        $totalScannedCount = 0;

        foreach ($scanDirectories as $dir) {
            if (! File::isDirectory($dir)) {
                continue;
            }

            $files = File::files($dir);
            foreach ($files as $file) {
                $totalScannedCount++;
                $filename = $file->getFilename();

                // Lewati berkas tersembunyi / dot files (.gitignore, .gitkeep)
                if (str_starts_with($filename, '.')) {
                    continue;
                }

                $ext = strtolower($file->getExtension());
                if (! empty($ext) && ! in_array($ext, $this->allowedExtensions, true)) {
                    continue;
                }

                // Cek apakah berkas memiliki catatan aktif di database
                if (isset($activeBasenames[$filename])) {
                    // Berkas aktif dan valid, jangan disentuh
                    continue;
                }

                // Cek apakah berkas terkait dengan catatan yang berada di Kotak Sampah
                if (isset($trashedBasenames[$filename])) {
                    if ($keepTrashed) {
                        continue;
                    }
                    $status = 'Kotak Sampah (Catatan non-aktif)';
                } else {
                    $status = 'Yatim Piatu (Tidak ada di database)';
                }

                $scannedFiles[] = [
                    'path' => $file->getPathname(),
                    'relative_dir' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $dir),
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'status' => $status,
                ];
            }
        }

        $orphanedCount = count($scannedFiles);

        if ($orphanedCount === 0) {
            $this->newLine();
            $this->info("✓ Selesai. Total {$totalScannedCount} berkas diperiksa.");
            $this->info('✓ Tidak ditemukan berkas yatim piatu (orphaned files). Direktori storage bersih.');
            return Command::SUCCESS;
        }

        $totalBytes = array_sum(array_column($scannedFiles, 'size'));
        $totalFormattedSize = $this->formatBytes($totalBytes);

        // 3. Tampilkan tabel ringkasan file yang terdeteksi
        $this->newLine();
        $this->warn("Ditemukan {$orphanedCount} berkas yang tidak memiliki relasi/catatan aktif di database ({$totalFormattedSize}):");

        $tableRows = [];
        foreach ($scannedFiles as $index => $item) {
            $tableRows[] = [
                $index + 1,
                $item['filename'],
                $item['relative_dir'],
                $this->formatBytes($item['size']),
                $item['status'],
            ];
        }

        $this->table(['No', 'Nama Berkas', 'Direktori', 'Ukuran', 'Status'], $tableRows);

        // 4. Periksa jika mode simulasi (--dry-run)
        if ($dryRun) {
            $this->newLine();
            $this->info("[SIMULASI] Mode --dry-run aktif. Tidak ada berkas fisik yang dihapus.");
            return Command::SUCCESS;
        }

        // 5. Konfirmasi sebelum penghapusan jika bukan --force
        if (! $force) {
            $confirm = $this->confirm(
                "Apakah Anda yakin ingin menghapus {$orphanedCount} berkas fisik di atas secara permanen dari storage?",
                false
            );

            if (! $confirm) {
                $this->info('Operasi pembersihan dibatalkan oleh pengguna.');
                return Command::SUCCESS;
            }
        }

        // 6. Eksekusi penghapusan berkas fisik
        $deletedCount = 0;
        $deletedBytes = 0;
        $failedCount = 0;

        foreach ($scannedFiles as $item) {
            try {
                if (File::exists($item['path'])) {
                    File::delete($item['path']);
                    $deletedCount++;
                    $deletedBytes += $item['size'];
                }
            } catch (\Throwable $e) {
                $failedCount++;
                $this->error("Gagal menghapus berkas {$item['filename']}: " . $e->getMessage());
            }
        }

        $freedSize = $this->formatBytes($deletedBytes);

        $this->newLine();
        $this->info("✓ Berhasil membersihkan {$deletedCount} berkas yatim piatu.");
        $this->info("✓ Ruang penyimpanan storage yang dibebaskan: {$freedSize}.");

        if ($failedCount > 0) {
            $this->warn("! Terdapat {$failedCount} berkas yang gagal dihapus karena kendala izin akses sistem.");
        }

        return Command::SUCCESS;
    }

    /**
     * Konversi ukuran byte menjadi format yang mudah dibaca (KB, MB, GB).
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
