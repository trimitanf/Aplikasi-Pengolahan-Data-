<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0; url={{ route('login') }}">
    <title>Sistem Informasi Pengolahan Data Iklan OTSK BPOM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-(--bg) flex items-center justify-center p-6 text-(--text)">
    <div class="text-center space-y-4 max-w-md">
        <div class="flex justify-center mb-2">
            <img src="{{ asset('assets/logo-bpom.jpeg') }}" alt="Logo BPOM" class="h-16 w-auto object-contain">
        </div>
        <h1 class="text-xl font-bold text-gray-900">Sistem Informasi Pengolahan Data Iklan</h1>
        <p class="text-sm text-gray-500">Direktorat Pengawasan OTSK - Badan Pengawas Obat dan Makanan</p>
        <div class="pt-2">
            <a
                href="{{ route('login') }}"
                class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-(--brand-blue) text-white text-sm font-semibold hover:bg-blue-700 transition-colors shadow-sm"
            >
                Masuk ke Aplikasi &rarr;
            </a>
        </div>
    </div>
</body>
</html>
