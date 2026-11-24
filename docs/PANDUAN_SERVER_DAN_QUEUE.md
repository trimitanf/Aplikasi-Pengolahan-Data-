# Panduan Pengelolaan Queue Worker & Deployment Server
### Aplikasi Pengawasan Iklan OTSK - BPOM RI

Dokumen ini menjelaskan tata cara penanganan proses latar belakang (Queue Worker) untuk pemrosesan dataset SIPT dan standardisasi berkas, baik pada server berbasis **Windows Server / Laragon** maupun **Linux (Ubuntu / CentOS)**.

---

## 1. Mekanisme Pemrosesan Dataset (Job & Worker)

Sistem menggunakan Job antrean Laravel:
- `App\Jobs\ProsesDatasetJob`: Membaca, memvalidasi 26 kolom file SIPT, mendeteksi inkonsistensi data mentah, dan memuatnya ke database.
- `App\Jobs\StandardizeDatasetJob`: Memproses aturan konversi dan pemetaan nilai ke tabel data final.

### Mekanisme Fallback Cerdas (`dispatchSync`)
Di dalam `DatasetController::store`, sistem telah dilengkapi logika fallback otomatis:
- Jika berkas berukuran wajar (**< 10 MB** atau diperkirakan **< 2.000 baris data**), atau jika `QUEUE_CONNECTION=sync`, sistem secara otomatis menjalankan **`ProsesDatasetJob::dispatchSync()`**.
- **Keuntungan**: Berkas langsung selesai diproses secara instan (*real-time*) tanpa pernah menggantung di status *Pending/Diunggah*, sekalipun server lupa menjalankan perintah `php artisan queue:work`.
- Untuk berkas sangat besar di atas 10 MB pada server *production*, sistem akan mendistribusikannya ke antrean antarmuka (`dispatch()`) agar tidak membebani memori web server HTTP.

---

## 2. Cara Menjalankan Queue Worker di Server Windows

### Opsi A: Menggunakan NSSM (Non-Sucking Service Manager) - Sangat Direkomendasikan
NSSM membuat perintah Laravel berjalan sebagai **Windows Service** resmi yang otomatis berjalan saat server dinyalakan (*auto-start*) dan otomatis restart jika terjadi crash.

1. Unduh NSSM dari [nssm.cc](https://nssm.cc/download).
2. Ekstrak `nssm.exe` ke folder sistem (misal `C:\nssm\nssm.exe`).
3. Buka **Command Prompt / PowerShell (Run as Administrator)**, lalu jalankan:
   ```cmd
   nssm install IklanOtskQueue
   ```
4. Di jendela GUI yang muncul:
   - **Path**: `C:\laragon\bin\php\php-8.3.x\php.exe` (sesuaikan path PHP Anda)
   - **Startup directory**: `C:\Project\iklan-otsk`
   - **Arguments**: `artisan queue:work --tries=3 --timeout=120 --sleep=3`
5. Klik **Install service**.
6. Jalankan servicenya:
   ```cmd
   nssm start IklanOtskQueue
   ```

### Opsi B: Menggunakan Windows Task Scheduler
1. Buka **Task Scheduler** di Windows.
2. Pilih **Create Basic Task** -> Beri nama `Laravel Queue Iklan OTSK`.
3. Trigger: Pilih **When the computer starts**.
4. Action: Pilih **Start a program**.
   - Program/script: `php.exe` (path lengkap ke php.exe Anda)
   - Add arguments: `artisan queue:work --sleep=3 --tries=3`
   - Start in: `C:\Project\iklan-otsk`
5. Centang **Open the Properties dialog** lalu pastikan opsi **Run whether user is logged on or not** diaktifkan.

### Opsi C: Manual untuk Pengembangan Lokal (Local Development)
Cukup buka terminal PowerShell di folder proyek dan jalankan:
```bash
php artisan queue:work --tries=3
```

---

## 3. Cara Menjalankan Queue Worker di Server Linux (Supervisor)

Untuk server berbasis Ubuntu/Debian di lingkungan *Production*, gunakan **Supervisor**:

1. Install supervisor:
   ```bash
   sudo apt-get update && sudo apt-get install supervisor -y
   ```
2. Buat file konfigurasi worker:
   ```bash
   sudo nano /etc/supervisor/conf.d/iklan-otsk-worker.conf
   ```
3. Masukkan konfigurasi berikut:
   ```ini
   [program:iklan-otsk-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/iklan-otsk/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/var/www/iklan-otsk/storage/logs/worker.log
   stopwaitsecs=3600
   ```
4. Muat ulang dan jalankan Supervisor:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start iklan-otsk-worker:*
   ```

---

## 4. Konfigurasi Alternatif di `.env`

Jika di server hosting bersama (*Shared Hosting*) tidak memiliki akses root/terminal untuk menjalankan *background worker*, Anda dapat menyetel:
```env
QUEUE_CONNECTION=sync
```
Dengan mode `sync`, seluruh berkas akan dieksekusi secara langsung dalam *lifecycle request* unggah.
