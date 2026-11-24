@props(['warnings' => []])

@if (!empty($warnings))
    <div class="p-4 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 flex items-start gap-3 shadow-xs" role="alert">
        <div class="shrink-0 mt-0.5">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div class="text-xs space-y-1 min-w-0">
            <p class="font-bold text-amber-950">
                Pemberitahuan: Ditemukan Kode Belum Terstandarisasi di Master Data
            </p>
            @if (!empty($warnings['upt']))
                <p class="text-amber-900 leading-relaxed">
                    Ditemukan nama pada kolom <strong>UPT</strong> yang belum terdaftar di Kamus Master Data:
                    <span class="font-semibold text-amber-950 bg-amber-100/70 px-1.5 py-0.5 rounded border border-amber-200/80">{{ implode(', ', $warnings['upt']) }}</span>.
                    Silakan hubungi Administrator untuk menambahkan kode standar UPT baru tersebut pada menu Master Data.
                </p>
            @endif
            @foreach ($warnings as $kolom => $items)
                @if ($kolom !== 'upt')
                    <p class="text-amber-900 leading-relaxed">
                        Ditemukan data pada kolom <strong>{{ strtoupper($kolom) }}</strong> yang belum terdaftar di Kamus Master Data:
                        <span class="font-semibold text-amber-950 bg-amber-100/70 px-1.5 py-0.5 rounded border border-amber-200/80">{{ implode(', ', (array) $items) }}</span>.
                        Silakan hubungi Administrator untuk menambahkan kode standar baru tersebut pada menu Master Data.
                    </p>
                @endif
            @endforeach
        </div>
    </div>
@endif
