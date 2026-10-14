<x-layouts.app title="Rekapitulasi Data Iklan (OBA & SK)">
    <x-slot:header>
        Rekapitulasi Pengawasan Iklan (OBA & SK)
    </x-slot:header>
    <x-slot:breadcrumb>
        Rekap
    </x-slot:breadcrumb>

    @php
        $oba = $rekap['oba'];
        $sk = $rekap['sk'];
        $formatRibuan = fn($num) => number_format((float)$num, 0, ',', '.');
        $formatPersen = fn($num) => number_format((float)$num, 2, ',', '.') . '%';
    @endphp

    <!-- Filter & Export Toolbar -->
    <x-card>
        <form method="GET" action="{{ route('rekap.index') }}" class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1">
                <!-- Tahun -->
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Tahun</label>
                    <select
                        name="tahun"
                        class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                    >
                        @for ($y = now()->year + 1; $y >= 2024; $y--)
                            <option value="{{ $y }}" @selected($tahun == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Rentang Bulan -->
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Bulan Awal</label>
                    <select
                        name="bulan_awal"
                        class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                    >
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($bulanAwal == $m)>
                                {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Bulan Akhir</label>
                    <select
                        name="bulan_akhir"
                        class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                    >
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($bulanAkhir == $m)>
                                {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <x-button type="submit" variant="primary" size="sm">
                    Terapkan Filter
                </x-button>

                <a
                    href="{{ route('rekap.export', request()->query()) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-800 text-xs font-semibold hover:bg-emerald-100 transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export Excel Rekap (.xlsx)</span>
                </a>
            </div>
        </form>
    </x-card>

    <!-- Side-by-Side Rekap Presentation (Matching Excel Sheet) -->
    <div class="bg-white rounded-2xl border border-[var(--border)] shadow-xs overflow-hidden">
        <!-- Top Meta Header -->
        <div class="p-6 border-b border-gray-200 bg-slate-50/70 text-right space-y-1">
            <h2 class="text-sm md:text-base font-extrabold text-gray-900 uppercase tracking-tight">
                SUMMARY DATA IKLAN OBAT BAHAN ALAM DAN SUPLEMEN KESEHATAN
            </h2>
            <p class="text-xs font-bold text-gray-700">TAHUN {{ $tahun }}</p>
            <p class="text-xs font-semibold text-gray-500 uppercase">
                UPDATE {{ $rekap['tanggal_update']->translatedFormat('d F Y') }}
            </p>
            <p class="text-xs font-bold text-blue-700">
                Nomor 1-{{ $formatRibuan($rekap['max_no']) }}
            </p>
            @if ($rekap['menunggu_evaluasi_count'] > 0)
                <p class="text-[11px] text-amber-600 italic">
                    * Catatan: Terdapat {{ $formatRibuan($rekap['menunggu_evaluasi_count']) }} baris berstatus menunggu evaluasi pusat (tidak dihitung dalam rekap capaian).
                </p>
            @endif
        </div>

        <!-- 2 Side-by-side Tables Container -->
        <div class="overflow-x-auto p-4 md:p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 min-w-[980px]">
                <!-- ================= LEFT TABLE: OBAT BAHAN ALAM (OBA) ================= -->
                <div class="border border-gray-300 rounded-xl overflow-hidden shadow-2xs">
                    <table class="w-full text-xs divide-y divide-gray-200">
                        <thead class="bg-gray-100 font-bold uppercase tracking-wider text-gray-800">
                            <tr>
                                <th class="px-3 py-3 text-left w-5/12">IKLAN OBAT BAHAN ALAM</th>
                                <th class="px-2 py-3 text-right">TOTAL</th>
                                <th class="px-2 py-3 text-right">MK</th>
                                <th class="px-2 py-3 text-right">% MK</th>
                                <th class="px-2 py-3 text-right">TMK</th>
                                <th class="px-2 py-3 text-right">% TMK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <!-- Total Pengawasan Iklan -->
                            <tr class="bg-[#FFF59D] font-bold text-gray-900">
                                <td class="px-3 py-2.5">Total Pengawasan Iklan</td>
                                <td class="px-2 py-2.5 text-right">{{ $formatRibuan($oba['total_pengawasan']['total']) }}</td>
                                <td class="px-2 py-2.5 text-right">{{ $formatRibuan($oba['total_pengawasan']['mk']) }}</td>
                                <td class="px-2 py-2.5 text-right">{{ $formatPersen($oba['total_pengawasan']['persen_mk']) }}</td>
                                <td class="px-2 py-2.5 text-right">{{ $formatRibuan($oba['total_pengawasan']['tmk']) }}</td>
                                <td class="px-2 py-2.5 text-right">{{ $formatPersen($oba['total_pengawasan']['persen_tmk']) }}</td>
                            </tr>

                            <!-- Asal Produk -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Rincian berdasarkan asal produk</td>
                            </tr>
                            @foreach (['lokal' => 'a. Lokal', 'impor' => 'b. Impor', 'lisensi' => 'c. Lisensi'] as $k => $lbl)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-3 py-1.5 pl-5 text-gray-700">{{ $lbl }}</td>
                                    <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($oba['asal_produk'][$k]['total']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-emerald-700">{{ $formatRibuan($oba['asal_produk'][$k]['mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($oba['asal_produk'][$k]['persen_mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-red-700">{{ $formatRibuan($oba['asal_produk'][$k]['tmk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($oba['asal_produk'][$k]['persen_tmk']) }}</td>
                                </tr>
                            @endforeach

                            <!-- Kategori Media -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Rincian berdasarkan kategori media</td>
                            </tr>
                            @foreach (['audio' => 'a. Audio', 'visual' => 'b. Visual', 'audiovisual' => 'c. Audiovisual'] as $k => $lbl)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-3 py-1.5 pl-5 text-gray-700">{{ $lbl }}</td>
                                    <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($oba['kategori_media'][$k]['total']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-emerald-700">{{ $formatRibuan($oba['kategori_media'][$k]['mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($oba['kategori_media'][$k]['persen_mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-red-700">{{ $formatRibuan($oba['kategori_media'][$k]['tmk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($oba['kategori_media'][$k]['persen_tmk']) }}</td>
                                </tr>
                            @endforeach

                            <!-- Kategori Iklan (OBA: 8 Baris) -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Rincian berdasarkan kategori iklan</td>
                            </tr>
                            @php
                                $iklanOBA = [
                                    'ecommerce' => 'a. E-Commerce',
                                    'website' => 'b. Website',
                                    'medsos' => 'c. Media Sosial',
                                    'internet_lain' => 'd. Internet Lain',
                                    'tv_nasional' => 'e. TV Nasional',
                                    'penyiaran_lokal' => 'f. Penyiaran Lokal',
                                    'luar_ruang' => 'g. Luar Ruang',
                                    'cetak' => 'h. Cetak',
                                ];
                            @endphp
                            @foreach ($iklanOBA as $k => $lbl)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-3 py-1.5 pl-5 text-gray-700">{{ $lbl }}</td>
                                    <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($oba['kategori_iklan'][$k]['total']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-emerald-700">{{ $formatRibuan($oba['kategori_iklan'][$k]['mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($oba['kategori_iklan'][$k]['persen_mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-red-700">{{ $formatRibuan($oba['kategori_iklan'][$k]['tmk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($oba['kategori_iklan'][$k]['persen_tmk']) }}</td>
                                </tr>
                            @endforeach

                            <!-- Pembuat Iklan -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Rincian berdasarkan pembuat iklan</td>
                            </tr>
                            @foreach (['pelaku_usaha' => 'a. Pelaku Usaha', 'perseorangan' => 'b. Perseorangan'] as $k => $lbl)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-3 py-1.5 pl-5 text-gray-700">{{ $lbl }}</td>
                                    <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($oba['pembuat_iklan'][$k]['total']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-emerald-700">{{ $formatRibuan($oba['pembuat_iklan'][$k]['mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($oba['pembuat_iklan'][$k]['persen_mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-red-700">{{ $formatRibuan($oba['pembuat_iklan'][$k]['tmk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($oba['pembuat_iklan'][$k]['persen_tmk']) }}</td>
                                </tr>
                            @endforeach

                            <!-- Pencantuman NIE -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Pencantuman NIE (E-Commerce)</td>
                            </tr>
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-3 py-1.5 pl-5 text-gray-700">a. Ada</td>
                                <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($oba['pencantuman_nie']['ada']) }}</td>
                                <td colspan="4" class="px-2 py-1.5"></td>
                            </tr>
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-3 py-1.5 pl-5 text-gray-700">b. Tidak Ada</td>
                                <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($oba['pencantuman_nie']['tidak_ada']) }}</td>
                                <td colspan="4" class="px-2 py-1.5"></td>
                            </tr>

                            <!-- Kesesuaian NIE -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Kesesuaian NIE (E-Commerce)</td>
                            </tr>
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-3 py-1.5 pl-5 text-gray-700">a. Sesuai</td>
                                <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($oba['kesesuaian_nie']['sesuai']) }}</td>
                                <td colspan="4" class="px-2 py-1.5"></td>
                            </tr>
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-3 py-1.5 pl-5 text-gray-700">b. Tidak Sesuai</td>
                                <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($oba['kesesuaian_nie']['tidak_sesuai']) }}</td>
                                <td colspan="4" class="px-2 py-1.5"></td>
                            </tr>

                            <!-- Rincian TMK -->
                            <tr class="bg-[#FFF59D] font-bold text-gray-900">
                                <td class="px-3 py-2">Rincian TMK</td>
                                <td class="px-2 py-2 text-right">TOTAL</td>
                                <td class="px-2 py-2 text-right">%</td>
                                <td colspan="3" class="px-2 py-2"></td>
                            </tr>
                            @php
                                $rincianTmkLabels = [
                                    'tie' => 'a. Produk tidak terdaftar',
                                    'testimoni' => 'b. Mencantumkan testimoni',
                                    'hadiah' => 'c. Memberikan hadiah',
                                    'klaim_berlebihan' => 'd. Mencantumkan klaim berlebihan',
                                    'lain_lain' => 'e. Lain-lain',
                                ];
                            @endphp
                            @foreach ($rincianTmkLabels as $k => $lbl)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-3 py-1.5 pl-5 text-gray-700">{{ $lbl }}</td>
                                    <td class="px-2 py-1.5 text-right font-medium text-red-700">{{ $formatRibuan($oba['rincian_tmk'][$k]['total']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($oba['rincian_tmk'][$k]['persen']) }}</td>
                                    <td colspan="3" class="px-2 py-1.5"></td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-3 py-1.5 pl-5">Total</td>
                                <td class="px-2 py-1.5 text-right text-red-800">{{ $formatRibuan($oba['rincian_tmk']['total']['total']) }}</td>
                                <td class="px-2 py-1.5 text-right">{{ $formatPersen($oba['rincian_tmk']['total']['persen']) }}</td>
                                <td colspan="3" class="px-2 py-1.5"></td>
                            </tr>

                            <!-- Capaian IKU OBA -->
                            <tr class="bg-[#E0F7FA] font-bold text-cyan-950 border-t-2 border-cyan-300">
                                <td class="px-3 py-2">TARGET IKU OBA MK {{ $tahun }}</td>
                                <td class="px-2 py-2 text-right">{{ $formatPersen($oba['target_iku']) }}</td>
                                <td colspan="4" class="px-2 py-2"></td>
                            </tr>
                            <tr class="bg-[#E0F7FA] font-bold text-cyan-950">
                                <td class="px-3 py-2">CAPAIAN IKU OBA MK</td>
                                <td class="px-2 py-2 text-right text-emerald-800">{{ $formatPersen($oba['capaian_iku']) }}</td>
                                <td colspan="4" class="px-2 py-2"></td>
                            </tr>
                            <tr class="bg-[#E0F7FA] font-bold text-cyan-950">
                                <td class="px-3 py-2">Persentase capaian IKU</td>
                                <td class="px-2 py-2 text-right text-blue-900">{{ $formatPersen($oba['persentase_capaian_iku']) }}</td>
                                <td colspan="4" class="px-2 py-2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ================= RIGHT TABLE: SUPLEMEN KESEHATAN (SK) ================= -->
                <div class="border border-gray-300 rounded-xl overflow-hidden shadow-2xs">
                    <table class="w-full text-xs divide-y divide-gray-200">
                        <thead class="bg-gray-100 font-bold uppercase tracking-wider text-gray-800">
                            <tr>
                                <th class="px-3 py-3 text-left w-5/12">IKLAN SUPLEMEN KESEHATAN</th>
                                <th class="px-2 py-3 text-right">TOTAL</th>
                                <th class="px-2 py-3 text-right">MK</th>
                                <th class="px-2 py-3 text-right">% MK</th>
                                <th class="px-2 py-3 text-right">TMK</th>
                                <th class="px-2 py-3 text-right">% TMK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <!-- Total Pengawasan Iklan -->
                            <tr class="bg-[#FFF59D] font-bold text-gray-900">
                                <td class="px-3 py-2.5">Total Pengawasan Iklan</td>
                                <td class="px-2 py-2.5 text-right">{{ $formatRibuan($sk['total_pengawasan']['total']) }}</td>
                                <td class="px-2 py-2.5 text-right">{{ $formatRibuan($sk['total_pengawasan']['mk']) }}</td>
                                <td class="px-2 py-2.5 text-right">{{ $formatPersen($sk['total_pengawasan']['persen_mk']) }}</td>
                                <td class="px-2 py-2.5 text-right">{{ $formatRibuan($sk['total_pengawasan']['tmk']) }}</td>
                                <td class="px-2 py-2.5 text-right">{{ $formatPersen($sk['total_pengawasan']['persen_tmk']) }}</td>
                            </tr>

                            <!-- Asal Produk -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Rincian berdasarkan asal produk</td>
                            </tr>
                            @foreach (['lokal' => 'a. Lokal', 'impor' => 'b. Impor', 'lisensi' => 'c. Lisensi'] as $k => $lbl)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-3 py-1.5 pl-5 text-gray-700">{{ $lbl }}</td>
                                    <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($sk['asal_produk'][$k]['total']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-emerald-700">{{ $formatRibuan($sk['asal_produk'][$k]['mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($sk['asal_produk'][$k]['persen_mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-red-700">{{ $formatRibuan($sk['asal_produk'][$k]['tmk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($sk['asal_produk'][$k]['persen_tmk']) }}</td>
                                </tr>
                            @endforeach

                            <!-- Kategori Media -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Rincian berdasarkan kategori media</td>
                            </tr>
                            @foreach (['audio' => 'a. Audio', 'visual' => 'b. Visual', 'audiovisual' => 'c. Audiovisual'] as $k => $lbl)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-3 py-1.5 pl-5 text-gray-700">{{ $lbl }}</td>
                                    <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($sk['kategori_media'][$k]['total']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-emerald-700">{{ $formatRibuan($sk['kategori_media'][$k]['mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($sk['kategori_media'][$k]['persen_mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-red-700">{{ $formatRibuan($sk['kategori_media'][$k]['tmk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($sk['kategori_media'][$k]['persen_tmk']) }}</td>
                                </tr>
                            @endforeach

                            <!-- Kategori Iklan (SK: 7 Baris) -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Rincian berdasarkan kategori iklan</td>
                            </tr>
                            @php
                                $iklanSK = [
                                    'ecommerce' => 'a. E-Commerce',
                                    'website' => 'b. Website',
                                    'medsos' => 'c. Media Sosial',
                                    'internet_lain' => 'd. Internet Lain',
                                    'tv_nasional' => 'e. TV Nasional',
                                    'luar_ruang' => 'g. Luar Ruang',
                                    'cetak' => 'h. Cetak',
                                ];
                            @endphp
                            @foreach ($iklanSK as $k => $lbl)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-3 py-1.5 pl-5 text-gray-700">{{ $lbl }}</td>
                                    <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($sk['kategori_iklan'][$k]['total']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-emerald-700">{{ $formatRibuan($sk['kategori_iklan'][$k]['mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($sk['kategori_iklan'][$k]['persen_mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-red-700">{{ $formatRibuan($sk['kategori_iklan'][$k]['tmk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($sk['kategori_iklan'][$k]['persen_tmk']) }}</td>
                                </tr>
                            @endforeach

                            <!-- Pembuat Iklan -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Rincian berdasarkan pembuat iklan</td>
                            </tr>
                            @foreach (['pelaku_usaha' => 'a. Pelaku Usaha', 'perseorangan' => 'b. Perseorangan'] as $k => $lbl)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-3 py-1.5 pl-5 text-gray-700">{{ $lbl }}</td>
                                    <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($sk['pembuat_iklan'][$k]['total']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-emerald-700">{{ $formatRibuan($sk['pembuat_iklan'][$k]['mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($sk['pembuat_iklan'][$k]['persen_mk']) }}</td>
                                    <td class="px-2 py-1.5 text-right text-red-700">{{ $formatRibuan($sk['pembuat_iklan'][$k]['tmk']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($sk['pembuat_iklan'][$k]['persen_tmk']) }}</td>
                                </tr>
                            @endforeach

                            <!-- Pencantuman NIE -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Pencantuman NIE (E-Commerce)</td>
                            </tr>
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-3 py-1.5 pl-5 text-gray-700">a. Ada</td>
                                <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($sk['pencantuman_nie']['ada']) }}</td>
                                <td colspan="4" class="px-2 py-1.5"></td>
                            </tr>
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-3 py-1.5 pl-5 text-gray-700">b. Tidak Ada</td>
                                <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($sk['pencantuman_nie']['tidak_ada']) }}</td>
                                <td colspan="4" class="px-2 py-1.5"></td>
                            </tr>

                            <!-- Kesesuaian NIE -->
                            <tr class="bg-[#FFF9C4] font-semibold text-gray-800">
                                <td colspan="6" class="px-3 py-1.5">Kesesuaian NIE (E-Commerce)</td>
                            </tr>
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-3 py-1.5 pl-5 text-gray-700">a. Sesuai</td>
                                <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($sk['kesesuaian_nie']['sesuai']) }}</td>
                                <td colspan="4" class="px-2 py-1.5"></td>
                            </tr>
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-3 py-1.5 pl-5 text-gray-700">b. Tidak Sesuai</td>
                                <td class="px-2 py-1.5 text-right font-medium">{{ $formatRibuan($sk['kesesuaian_nie']['tidak_sesuai']) }}</td>
                                <td colspan="4" class="px-2 py-1.5"></td>
                            </tr>

                            <!-- Rincian TMK -->
                            <tr class="bg-[#FFF59D] font-bold text-gray-900">
                                <td class="px-3 py-2">Rincian TMK</td>
                                <td class="px-2 py-2 text-right">TOTAL</td>
                                <td class="px-2 py-2 text-right">%</td>
                                <td colspan="3" class="px-2 py-2"></td>
                            </tr>
                            @foreach ($rincianTmkLabels as $k => $lbl)
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-3 py-1.5 pl-5 text-gray-700">{{ $lbl }}</td>
                                    <td class="px-2 py-1.5 text-right font-medium text-red-700">{{ $formatRibuan($sk['rincian_tmk'][$k]['total']) }}</td>
                                    <td class="px-2 py-1.5 text-right">{{ $formatPersen($sk['rincian_tmk'][$k]['persen']) }}</td>
                                    <td colspan="3" class="px-2 py-1.5"></td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-3 py-1.5 pl-5">Total</td>
                                <td class="px-2 py-1.5 text-right text-red-800">{{ $formatRibuan($sk['rincian_tmk']['total']['total']) }}</td>
                                <td class="px-2 py-1.5 text-right">{{ $formatPersen($sk['rincian_tmk']['total']['persen']) }}</td>
                                <td colspan="3" class="px-2 py-1.5"></td>
                            </tr>

                            <!-- Capaian IKU SK -->
                            <tr class="bg-[#E0F7FA] font-bold text-cyan-950 border-t-2 border-cyan-300">
                                <td class="px-3 py-2">TARGET IKU SK MK {{ $tahun }}</td>
                                <td class="px-2 py-2 text-right">{{ $formatPersen($sk['target_iku']) }}</td>
                                <td colspan="4" class="px-2 py-2"></td>
                            </tr>
                            <tr class="bg-[#E0F7FA] font-bold text-cyan-950">
                                <td class="px-3 py-2">CAPAIAN IKU SK MK</td>
                                <td class="px-2 py-2 text-right text-emerald-800">{{ $formatPersen($sk['capaian_iku']) }}</td>
                                <td colspan="4" class="px-2 py-2"></td>
                            </tr>
                            <tr class="bg-[#E0F7FA] font-bold text-cyan-950">
                                <td class="px-3 py-2">Persentase capaian IKU</td>
                                <td class="px-2 py-2 text-right text-blue-900">{{ $formatPersen($sk['persentase_capaian_iku']) }}</td>
                                <td colspan="4" class="px-2 py-2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Kecil: Kelengkapan Periode (Bulan x Komoditi) -->
    <x-card title="Kelengkapan Periode Data Final" subtitle="Status finalisasi dataset per bulan untuk komoditi OT, OK, dan SK">
        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-center">
                <thead class="bg-gray-50 font-bold text-gray-600 uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Komoditi</th>
                        @for ($m = 1; $m <= 12; $m++)
                            <th class="px-2.5 py-2">{{ substr(DateTime::createFromFormat('!m', $m)->format('M'), 0, 3) }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white font-medium">
                    @foreach (['OT' => 'Obat Tradisional (OT)', 'OK' => 'Obat Kuasi (OK)', 'SK' => 'Suplemen Kesehatan (SK)'] as $kom => $nama)
                        <tr>
                            <td class="px-3 py-2.5 text-left font-bold text-gray-900">{{ $nama }}</td>
                            @for ($m = 1; $m <= 12; $m++)
                                @php
                                    $isFinalized = $rekap['kelengkapan'][$m][$kom] ?? false;
                                @endphp
                                <td class="px-2.5 py-2.5 text-center">
                                    @if ($isFinalized)
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold" title="Final">✓</span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-100 text-gray-400 text-xs" title="Belum Final">-</span>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts.app>
