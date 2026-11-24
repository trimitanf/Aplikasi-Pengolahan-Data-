@echo off
echo Membuat arsip zip bersih proyek (bebas node_modules, .env, .git, database.sqlite)...
git archive --format=zip --output=iklan-otsk-clean.zip HEAD
if %ERRORLEVEL% EQU 0 (
    echo [SUKSES] Arsip bersih berhasil dibuat: iklan-otsk-clean.zip
) else (
    echo [GAGAL] Terjadi kesalahan saat membuat arsip git.
)
