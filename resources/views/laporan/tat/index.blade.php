@section('title', 'Laporan Turn Around Time (TAT)')

<x-app-layout>
    <div class="py-4 bg-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            
            <!-- Page Header Card & Filter Bar (Flat v2.0) -->
            <div class="bg-white border border-slate-200 rounded p-4 space-y-3">
                <!-- Top Row: Navigation & Meta -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('laporan.index') }}" class="px-2.5 py-1.5 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition-colors">
                            &larr; Pusat Laporan
                        </a>
                        <div>
                            <h1 class="text-base font-bold text-slate-900">Laporan Turn Around Time (TAT)</h1>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Evaluasi kecepatan pelayanan pengujian
                                <span class="font-semibold text-rose-700">CITO (&le; <span class="lbl-cito-short">{{ $settings['cito_sla_short'] ?? '2 Jam (120m)' }}</span>)</span>
                                dan
                                <span class="font-semibold text-blue-700">Non-CITO (&le; <span class="lbl-noncito-short">{{ $settings['non_cito_sla_short'] ?? '4 Jam (240m)' }}</span>)</span>.
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span id="cache-badge" class="px-2 py-1 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Cache Aktif: <span id="cache-time" class="font-mono">-</span>
                        </span>
                        <button id="toggle-setup-button" type="button" class="px-2.5 py-1 text-[11px] font-semibold rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition-colors flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Setup Target TAT</span>
                        </button>
                        <button id="refresh-button" type="button" title="Muat ulang data segar dari server LIS" class="px-2.5 py-1 text-[11px] font-semibold rounded bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-300 transition-colors flex items-center gap-1">
                            <span>Perbarui Data</span>
                        </button>
                    </div>
                </div>

                <!-- Dynamic SLA Setup Panel (Collapsible) -->
                <div id="sla-setup-panel" class="hidden bg-slate-50 border border-indigo-200 rounded p-3.5 space-y-3">
                    <div class="border-b border-slate-200 pb-2">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            Pengaturan Dinamis Standar Target TAT (SLA)
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Atur batas waktu maksimal pengerjaan laboratorium untuk evaluasi kepatuhan TAT CITO dan Non-CITO.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <!-- Input Target CITO -->
                        <div class="md:col-span-4 bg-white border border-rose-200 rounded p-2.5">
                            <label class="block text-[11px] font-bold text-rose-900 uppercase mb-1.5">
                                Target Maksimal TAT CITO
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <span class="text-[10px] text-slate-500 block mb-0.5">Dalam Jam</span>
                                    <div class="flex items-center">
                                        <input type="number" id="setup_cito_hours" step="0.5" min="0.25" max="24"
                                               value="{{ round(($settings['cito_sla_mins'] ?? 120) / 60, 2) }}"
                                               class="w-full h-8 px-2 text-xs font-mono font-bold border border-slate-300 rounded-l outline-none focus:border-rose-500">
                                        <span class="h-8 px-2 bg-slate-100 border border-l-0 border-slate-300 rounded-r text-[11px] text-slate-600 flex items-center font-semibold">Jam</span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-500 block mb-0.5">Dalam Menit</span>
                                    <div class="flex items-center">
                                        <input type="number" id="setup_cito_mins" step="5" min="15" max="1440"
                                               value="{{ $settings['cito_sla_mins'] ?? 120 }}"
                                               class="w-full h-8 px-2 text-xs font-mono font-bold border border-slate-300 rounded-l outline-none focus:border-rose-500">
                                        <span class="h-8 px-2 bg-slate-100 border border-l-0 border-slate-300 rounded-r text-[11px] text-slate-600 flex items-center font-semibold">Mnt</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Target Non-CITO -->
                        <div class="md:col-span-4 bg-white border border-blue-200 rounded p-2.5">
                            <label class="block text-[11px] font-bold text-blue-900 uppercase mb-1.5">
                                Target Maksimal TAT Non-CITO (Rutin)
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <span class="text-[10px] text-slate-500 block mb-0.5">Dalam Jam</span>
                                    <div class="flex items-center">
                                        <input type="number" id="setup_noncito_hours" step="0.5" min="0.25" max="48"
                                               value="{{ round(($settings['non_cito_sla_mins'] ?? 240) / 60, 2) }}"
                                               class="w-full h-8 px-2 text-xs font-mono font-bold border border-slate-300 rounded-l outline-none focus:border-blue-500">
                                        <span class="h-8 px-2 bg-slate-100 border border-l-0 border-slate-300 rounded-r text-[11px] text-slate-600 flex items-center font-semibold">Jam</span>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-500 block mb-0.5">Dalam Menit</span>
                                    <div class="flex items-center">
                                        <input type="number" id="setup_noncito_mins" step="5" min="15" max="2880"
                                               value="{{ $settings['non_cito_sla_mins'] ?? 240 }}"
                                               class="w-full h-8 px-2 text-xs font-mono font-bold border border-slate-300 rounded-l outline-none focus:border-blue-500">
                                        <span class="h-8 px-2 bg-slate-100 border border-l-0 border-slate-300 rounded-r text-[11px] text-slate-600 flex items-center font-semibold">Mnt</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="md:col-span-4 flex flex-col justify-between gap-2">
                            <div id="setup-feedback" class="hidden text-[11px] font-semibold px-2.5 py-1.5 rounded"></div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="apply-sla-btn" class="flex-1 h-9 px-3 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded transition-colors">
                                    Terapkan ke Laporan
                                </button>
                                <button type="button" id="save-sla-default-btn" class="flex-1 h-9 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded transition-colors">
                                    Simpan Default
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row: Unified Filter Controls -->
                <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                    <!-- Date Presets & Custom Pickers -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Presets Segmented Control -->
                        <div class="inline-flex border border-slate-300 rounded overflow-hidden bg-slate-100">
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300" data-preset="today">Hari Ini</button>
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300" data-preset="7d">7 Hari</button>
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300" data-preset="30d">30 Hari</button>
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300 active-preset bg-white text-blue-700" data-preset="this_month">Bulan Ini</button>
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300" data-preset="last_month">Bulan Lalu</button>
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors" data-preset="this_year">Tahun Ini</button>
                        </div>

                        <!-- Date Inputs -->
                        <div class="flex items-center gap-1.5 bg-slate-50 px-2 py-1 border border-slate-300 rounded">
                            <input type="date" id="start_date" name="start_date" class="h-7 px-2 bg-white border border-slate-300 rounded text-xs text-slate-800 outline-none focus:border-blue-600 font-mono">
                            <span class="text-slate-400 text-xs font-medium">s/d</span>
                            <input type="date" id="end_date" name="end_date" class="h-7 px-2 bg-white border border-slate-300 rounded text-xs text-slate-800 outline-none focus:border-blue-600 font-mono">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <button id="search-button" type="button" class="h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded border border-blue-700 transition-colors flex items-center gap-1.5">
                            <span id="search-text">Tampilkan</span>
                        </button>

                        <button id="export-excel-button" type="button" class="h-9 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded border border-emerald-700 transition-colors flex items-center gap-1.5 whitespace-nowrap" title="Unduh laporan Excel 3-Sheet (Rekap CITO, Rekap Non-CITO, dan Log Check-in Pasien)">
                            <span>Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards (4 Columns with Skeleton Support) -->
            <div id="kpi-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- KPI 1: Total CITO -->
                <div class="bg-white border border-rose-200 rounded p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-rose-900 uppercase tracking-wider">Volume Uji CITO</span>
                        <span class="px-1.5 py-0.5 text-[10px] font-bold bg-rose-100 text-rose-800 rounded">Prioritas</span>
                    </div>
                    <div class="mt-2" id="kpi-cito-val">
                        <div class="text-2xl font-black text-rose-700 font-mono" id="kpiTotalCito">-</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Rata-rata TAT: <span id="kpiAvgCito" class="font-bold text-slate-800">-</span></div>
                    </div>
                </div>

                <!-- KPI 2: Kepatuhan Target CITO -->
                <div class="bg-white border border-rose-200 rounded p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-rose-900 uppercase tracking-wider">Kepatuhan Target CITO</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-800">&le; <span class="lbl-cito-short">{{ $settings['cito_sla_short'] ?? '2 Jam (120m)' }}</span></span>
                    </div>
                    <div class="mt-2" id="kpi-cito-sla-val">
                        <div class="text-2xl font-black text-rose-700 font-mono" id="kpiSlaCito">-</div>
                        <div class="text-[11px] text-slate-500 mt-0.5" id="kpiCitoDetail">Target TAT CITO &le; <span class="lbl-cito-label">{{ $settings['cito_sla_label'] ?? '2 Jam (120 Menit)' }}</span></div>
                    </div>
                </div>

                <!-- KPI 3: Total NON-CITO -->
                <div class="bg-white border border-blue-200 rounded p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-blue-900 uppercase tracking-wider">Volume Uji Non-CITO</span>
                        <span class="px-1.5 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-800 rounded">Rutin</span>
                    </div>
                    <div class="mt-2" id="kpi-noncito-val">
                        <div class="text-2xl font-black text-blue-700 font-mono" id="kpiTotalNonCito">-</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Rata-rata TAT: <span id="kpiAvgNonCito" class="font-bold text-slate-800">-</span></div>
                    </div>
                </div>

                <!-- KPI 4: Kepatuhan Target NON-CITO -->
                <div class="bg-white border border-blue-200 rounded p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-blue-900 uppercase tracking-wider">Kepatuhan Non-CITO</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-800">&le; <span class="lbl-noncito-short">{{ $settings['non_cito_sla_short'] ?? '4 Jam (240m)' }}</span></span>
                    </div>
                    <div class="mt-2" id="kpi-noncito-sla-val">
                        <div class="text-2xl font-black text-blue-700 font-mono" id="kpiSlaNonCito">-</div>
                        <div class="text-[11px] text-slate-500 mt-0.5" id="kpiNonCitoDetail">Target TAT Non-CITO &le; <span class="lbl-noncito-label">{{ $settings['non_cito_sla_label'] ?? '4 Jam (240 Menit)' }}</span></div>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: TAT CITO -->
            <div class="bg-white border border-slate-200 rounded p-4 space-y-3">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-200 pb-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">TAT CITO - Berdasarkan Nama Pemeriksaan</h2>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300 rounded">Target CITO &le; <span class="lbl-cito-short">{{ $settings['cito_sla_short'] ?? '2 Jam (120m)' }}</span></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        <select id="filter-cito-status" class="h-8 px-2.5 text-xs border border-slate-300 rounded bg-white text-slate-700 outline-none focus:border-blue-600">
                            <option value="all">Semua Pemeriksaan</option>
                            <option value="over">Rata-rata Melewati Target SLA</option>
                            <option value="compliant">Rata-rata Sesuai Target SLA</option>
                        </select>
                        <input type="text" id="filter-cito-input" placeholder="Cari kode / nama pemeriksaan CITO..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-blue-600 w-full sm:w-64">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border border-slate-200 text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200 text-center">
                            <tr>
                                <th class="p-2.5 border border-slate-200 w-10" rowspan="2">No</th>
                                <th class="p-2.5 border border-slate-200 w-20 text-left" rowspan="2">Kode</th>
                                <th class="p-2.5 border border-slate-200 text-left" rowspan="2">Nama Test</th>
                                <th class="p-2 border border-slate-200 text-center" colspan="2">Rawat Jalan</th>
                                <th class="p-2 border border-slate-200 text-center" colspan="2">Rawat Inap</th>
                                <th class="p-2 border border-slate-200 text-center" colspan="2">Lainnya</th>
                                <th class="p-2 border border-slate-200 text-center bg-slate-200" colspan="2">Total Keseluruhan</th>
                                <th class="p-2.5 border border-slate-200 w-32 text-center" rowspan="2">Kepatuhan (&le; <span class="lbl-cito-short">{{ $settings['cito_sla_short'] ?? '2 Jam (120m)' }}</span>)</th>
                            </tr>
                            <tr>
                                <th class="p-2 border border-slate-200 w-20 text-center">TAT</th>
                                <th class="p-2 border border-slate-200 w-16 text-center">Total</th>
                                <th class="p-2 border border-slate-200 w-20 text-center">TAT</th>
                                <th class="p-2 border border-slate-200 w-16 text-center">Total</th>
                                <th class="p-2 border border-slate-200 w-20 text-center">TAT</th>
                                <th class="p-2 border border-slate-200 w-16 text-center">Total</th>
                                <th class="p-2 border border-slate-200 w-24 text-center bg-slate-200">Rata-rata TAT</th>
                                <th class="p-2 border border-slate-200 w-20 text-center bg-slate-200">Total Uji</th>
                            </tr>
                        </thead>
                        <tbody id="body-cito" class="divide-y divide-slate-200 text-slate-800">
                            <!-- Skeleton rows loaded initially -->
                        </tbody>
                        <tfoot id="foot-cito" class="bg-slate-100 font-bold border-t-2 border-slate-300"></tfoot>
                    </table>
                </div>
            </div>

            <!-- SECTION 2: TAT NON-CITO -->
            <div class="bg-white border border-slate-200 rounded p-4 space-y-3">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-200 pb-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">TAT NON-CITO - Berdasarkan Nama Pemeriksaan</h2>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300 rounded">Target Non-CITO &le; <span class="lbl-noncito-short">{{ $settings['non_cito_sla_short'] ?? '4 Jam (240m)' }}</span></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        <select id="filter-noncito-status" class="h-8 px-2.5 text-xs border border-slate-300 rounded bg-white text-slate-700 outline-none focus:border-blue-600">
                            <option value="all">Semua Pemeriksaan</option>
                            <option value="over">Rata-rata Melewati Target SLA</option>
                            <option value="compliant">Rata-rata Sesuai Target SLA</option>
                        </select>
                        <input type="text" id="filter-noncito-input" placeholder="Cari kode / nama pemeriksaan Non-CITO..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-blue-600 w-full sm:w-64">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border border-slate-200 text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200 text-center">
                            <tr>
                                <th class="p-2.5 border border-slate-200 w-10" rowspan="2">No</th>
                                <th class="p-2.5 border border-slate-200 w-20 text-left" rowspan="2">Kode</th>
                                <th class="p-2.5 border border-slate-200 text-left" rowspan="2">Nama Test</th>
                                <th class="p-2 border border-slate-200 text-center" colspan="2">Rawat Jalan</th>
                                <th class="p-2 border border-slate-200 text-center" colspan="2">Rawat Inap</th>
                                <th class="p-2 border border-slate-200 text-center" colspan="2">Lainnya</th>
                                <th class="p-2 border border-slate-200 text-center bg-slate-200" colspan="2">Total Keseluruhan</th>
                                <th class="p-2.5 border border-slate-200 w-32 text-center" rowspan="2">Kepatuhan (&le; <span class="lbl-noncito-short">{{ $settings['non_cito_sla_short'] ?? '4 Jam (240m)' }}</span>)</th>
                            </tr>
                            <tr>
                                <th class="p-2 border border-slate-200 w-20 text-center">TAT</th>
                                <th class="p-2 border border-slate-200 w-16 text-center">Total</th>
                                <th class="p-2 border border-slate-200 w-20 text-center">TAT</th>
                                <th class="p-2 border border-slate-200 w-16 text-center">Total</th>
                                <th class="p-2 border border-slate-200 w-20 text-center">TAT</th>
                                <th class="p-2 border border-slate-200 w-16 text-center">Total</th>
                                <th class="p-2 border border-slate-200 w-24 text-center bg-slate-200">Rata-rata TAT</th>
                                <th class="p-2 border border-slate-200 w-20 text-center bg-slate-200">Total Uji</th>
                            </tr>
                        </thead>
                        <tbody id="body-noncito" class="divide-y divide-slate-200 text-slate-800">
                            <!-- Skeleton rows loaded initially -->
                        </tbody>
                        <tfoot id="foot-noncito" class="bg-slate-100 font-bold border-t-2 border-slate-300"></tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const now = new Date();
        const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
        document.getElementById('start_date').value = startOfMonth.toISOString().split('T')[0];
        document.getElementById('end_date').value = now.toISOString().split('T')[0];

        // Toggle Setup Panel
        $('#toggle-setup-button').on('click', function() {
            $('#sla-setup-panel').slideToggle(150);
        });

        // Sync Jam <-> Menit inputs in Setup Panel
        const citoHoursInput = $('#setup_cito_hours');
        const citoMinsInput = $('#setup_cito_mins');
        const nonCitoHoursInput = $('#setup_noncito_hours');
        const nonCitoMinsInput = $('#setup_noncito_mins');

        citoHoursInput.on('input', function() {
            const h = parseFloat($(this).val());
            if (!isNaN(h) && h > 0) {
                citoMinsInput.val(Math.round(h * 60));
            }
        });

        citoMinsInput.on('input', function() {
            const m = parseInt($(this).val(), 10);
            if (!isNaN(m) && m > 0) {
                citoHoursInput.val(Math.round((m / 60) * 100) / 100);
            }
        });

        nonCitoHoursInput.on('input', function() {
            const h = parseFloat($(this).val());
            if (!isNaN(h) && h > 0) {
                nonCitoMinsInput.val(Math.round(h * 60));
            }
        });

        nonCitoMinsInput.on('input', function() {
            const m = parseInt($(this).val(), 10);
            if (!isNaN(m) && m > 0) {
                nonCitoHoursInput.val(Math.round((m / 60) * 100) / 100);
            }
        });

        // Apply SLA Temporarily
        $('#apply-sla-btn').on('click', function() {
            fetchReportData(false);
        });

        // Save SLA as Permanent Default
        $('#save-sla-default-btn').on('click', function() {
            const btn = $(this);
            const feedback = $('#setup-feedback');
            const citoM = parseInt(citoMinsInput.val(), 10) || 120;
            const nonCitoM = parseInt(nonCitoMinsInput.val(), 10) || 240;

            btn.prop('disabled', true).text('Menyimpan...');
            feedback.addClass('hidden');

            $.ajax({
                url: "{{ route('laporan.tat.settings', [], false) }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    cito_sla_mins: citoM,
                    non_cito_sla_mins: nonCitoM
                },
                success: function(res) {
                    feedback.removeClass('hidden bg-rose-100 text-rose-800')
                            .addClass('bg-emerald-100 text-emerald-800')
                            .text(res.message || 'Standar target TAT berhasil disimpan.');
                    if (res.settings) {
                        updateSlaLabels(res.settings);
                    }
                    fetchReportData(false);
                },
                error: function() {
                    feedback.removeClass('hidden bg-emerald-100 text-emerald-800')
                            .addClass('bg-rose-100 text-rose-800')
                            .text('Gagal menyimpan pengaturan target TAT.');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Simpan Default');
                }
            });
        });

        function updateSlaLabels(cfg) {
            if (!cfg) return;
            if (cfg.cito_sla_short) $('.lbl-cito-short').text(cfg.cito_sla_short);
            if (cfg.cito_sla_label) $('.lbl-cito-label').text(cfg.cito_sla_label);
            if (cfg.non_cito_sla_short) $('.lbl-noncito-short').text(cfg.non_cito_sla_short);
            if (cfg.non_cito_sla_label) $('.lbl-noncito-label').text(cfg.non_cito_sla_label);
        }

        // Date Presets Handler
        document.querySelectorAll('.dash-preset').forEach(btn => {
            btn.addEventListener('click', function() {
                $('.dash-preset').removeClass('active-preset bg-white text-blue-700');
                $(this).addClass('active-preset bg-white text-blue-700');

                const preset = this.getAttribute('data-preset');
                const toDate = new Date();
                let fromDate = new Date();

                if (preset === 'today') {
                } else if (preset === '7d') {
                    fromDate.setDate(toDate.getDate() - 7);
                } else if (preset === '30d') {
                    fromDate.setDate(toDate.getDate() - 30);
                } else if (preset === 'this_month') {
                    fromDate = new Date(toDate.getFullYear(), toDate.getMonth(), 1);
                } else if (preset === 'last_month') {
                    fromDate = new Date(toDate.getFullYear(), toDate.getMonth() - 1, 1);
                    toDate.setDate(0);
                } else if (preset === 'this_year') {
                    fromDate = new Date(toDate.getFullYear(), 0, 1);
                }

                document.getElementById('start_date').value = fromDate.toISOString().split('T')[0];
                document.getElementById('end_date').value = toDate.toISOString().split('T')[0];
                fetchReportData(false);
            });
        });

        // Skeleton Generator
        function showSkeletons() {
            $('#kpiTotalCito, #kpiAvgCito, #kpiSlaCito, #kpiTotalNonCito, #kpiAvgNonCito, #kpiSlaNonCito').html(
                '<span class="inline-block h-6 w-24 bg-slate-200 animate-pulse rounded"></span>'
            );

            let skelHtml = '';
            for (let i = 0; i < 6; i++) {
                skelHtml += `
                    <tr class="animate-pulse">
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-4 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200"><div class="h-3 w-12 bg-slate-200 rounded"></div></td>
                        <td class="p-2 border border-slate-200"><div class="h-3 w-36 bg-slate-200 rounded"></div></td>
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-10 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-8 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-10 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-8 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-10 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-8 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200 text-center bg-slate-50"><div class="h-3 w-14 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200 text-center bg-slate-50"><div class="h-3 w-10 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-10 bg-slate-200 rounded mx-auto"></div></td>
                    </tr>
                `;
            }
            $('#body-cito').html(skelHtml);
            $('#body-noncito').html(skelHtml);
            $('#foot-cito, #foot-noncito').empty();
        }

        function renderRows(items, targetBodyId, targetFootId, searchClass, summaryMeta) {
            const tbody = $(targetBodyId);
            const tfoot = $(targetFootId);
            tbody.empty();
            tfoot.empty();

            if (!items || items.length === 0) {
                tbody.html('<tr><td colspan="12" class="p-6 text-center text-slate-400">Tidak ada data pemeriksaan pada periode ini.</td></tr>');
                return;
            }

            let sumRajal = 0, sumRanap = 0, sumLainnya = 0, sumTotal = 0, sumOnTime = 0;
            let tbHtml = '';

            items.forEach((item, idx) => {
                sumRajal += (item.rajal_count || 0);
                sumRanap += (item.ranap_count || 0);
                sumLainnya += (item.lainnya_count || 0);
                sumTotal += (item.total_count || 0);
                sumOnTime += (item.on_time_count || 0);

                const slaClass = item.sla_percent >= 90
                    ? 'text-emerald-700 bg-emerald-50 border border-emerald-200'
                    : (item.sla_percent >= 75
                        ? 'text-amber-700 bg-amber-50 border border-amber-200'
                        : 'text-rose-700 bg-rose-50 border border-rose-200');

                const overallTatClass = item.is_avg_compliant === false
                    ? 'text-rose-700 bg-rose-50/60'
                    : 'text-slate-900 bg-slate-50';

                const complianceAttr = item.is_avg_compliant === false ? 'over' : 'compliant';

                tbHtml += `
                    <tr class="hover:bg-slate-50 ${searchClass}" data-compliance="${complianceAttr}">
                        <td class="p-2 border border-slate-200 text-center text-slate-400 row-number">${idx + 1}</td>
                        <td class="p-2 border border-slate-200 font-mono font-medium text-slate-600 text-left target-search-code">${item.code}</td>
                        <td class="p-2 border border-slate-200 font-semibold text-slate-900 text-left target-search-text">${item.name}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-semibold text-blue-700">${item.rajal_tat_formatted}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono">${item.rajal_count > 0 ? item.rajal_count.toLocaleString() : '-'}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-semibold text-emerald-700">${item.ranap_tat_formatted}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono">${item.ranap_count > 0 ? item.ranap_count.toLocaleString() : '-'}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-semibold text-amber-700">${item.lainnya_tat_formatted || '-'}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono">${item.lainnya_count > 0 ? item.lainnya_count.toLocaleString() : '-'}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-bold ${overallTatClass}" title="${item.is_avg_compliant === false ? 'Rata-rata melewati target SLA' : 'Rata-rata memenuhi target SLA'}">${item.overall_tat_formatted}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-bold bg-slate-50">${item.total_count.toLocaleString()}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-bold">
                            <span class="px-1.5 py-0.5 rounded text-[11px] ${slaClass}">${item.sla_percent}%</span>
                        </td>
                    </tr>
                `;
            });
            tbody.html(tbHtml);

            // Render Total Footer with Weighted Averages
            const footSlaPct = sumTotal > 0 ? ((sumOnTime / sumTotal) * 100).toFixed(1).replace(/\.0$/, '') : 0;
            const footSlaClass = footSlaPct >= 90 ? 'text-emerald-700' : (footSlaPct >= 75 ? 'text-amber-700' : 'text-rose-700');

            const footHtml = `
                <tr>
                    <td colspan="3" class="p-2 text-left font-bold">TOTAL / RATA-RATA KESELURUHAN</td>
                    <td class="p-2 text-center font-mono font-bold text-blue-700">${summaryMeta.rajalAvg || '-'}</td>
                    <td class="p-2 text-center font-mono font-bold">${sumRajal.toLocaleString()}</td>
                    <td class="p-2 text-center font-mono font-bold text-emerald-700">${summaryMeta.ranapAvg || '-'}</td>
                    <td class="p-2 text-center font-mono font-bold">${sumRanap.toLocaleString()}</td>
                    <td class="p-2 text-center font-mono font-bold text-amber-700">${summaryMeta.lainnyaAvg || '-'}</td>
                    <td class="p-2 text-center font-mono font-bold">${sumLainnya.toLocaleString()}</td>
                    <td class="p-2 text-center font-mono font-black bg-slate-200 text-slate-900">${summaryMeta.overallAvg || '-'}</td>
                    <td class="p-2 text-center font-mono font-black bg-slate-200 text-slate-900">${sumTotal.toLocaleString()}</td>
                    <td class="p-2 text-center font-mono font-black ${footSlaClass}">${footSlaPct}%</td>
                </tr>
            `;
            tfoot.html(footHtml);
        }

        function fetchReportData(forceRefresh = false) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const citoSla = parseInt(citoMinsInput.val(), 10) || 120;
            const nonCitoSla = parseInt(nonCitoMinsInput.val(), 10) || 240;

            const btn = $('#search-button');
            const btnText = $('#search-text');

            showSkeletons();
            btnText.text('Memuat...');
            btn.prop('disabled', true).addClass('opacity-60');

            $.ajax({
                url: "{{ route('laporan.tat.data', [], false) }}",
                type: "GET",
                timeout: 180000, // 3 menit timeout untuk rentang data besar (1 tahun)
                data: { 
                    start_date: startDate, 
                    end_date: endDate,
                    cito_sla: citoSla,
                    non_cito_sla: nonCitoSla,
                    refresh: forceRefresh ? 1 : 0
                },
                success: function(res) {
                    const sum = res.summary || {};
                    if (res.sla_config) {
                        updateSlaLabels(res.sla_config);
                    }

                    const citoTot = Number(sum.cito_total || 0);
                    const citoOnTime = Number(sum.cito_on_time || 0);
                    const citoOver = Number(sum.cito_over_sla || 0);

                    const nonCitoTot = Number(sum.non_cito_total || 0);
                    const nonCitoOnTime = Number(sum.non_cito_on_time || 0);
                    const nonCitoOver = Number(sum.non_cito_over_sla || 0);

                    $('#kpiTotalCito').text(citoTot.toLocaleString() + ' Uji');
                    $('#kpiAvgCito').text(sum.cito_avg_tat || '-');
                    $('#kpiSlaCito').text((sum.cito_sla_percent || 0) + '%');
                    $('#kpiCitoDetail').html(
                        `<span class="text-emerald-700 font-semibold">${citoOnTime.toLocaleString()} Tepat</span> &bull; <span class="text-rose-600 font-semibold">${citoOver.toLocaleString()} Lewat</span>`
                    );

                    $('#kpiTotalNonCito').text(nonCitoTot.toLocaleString() + ' Uji');
                    $('#kpiAvgNonCito').text(sum.non_cito_avg_tat || '-');
                    $('#kpiSlaNonCito').text((sum.non_cito_sla_percent || 0) + '%');
                    $('#kpiNonCitoDetail').html(
                        `<span class="text-emerald-700 font-semibold">${nonCitoOnTime.toLocaleString()} Tepat</span> &bull; <span class="text-rose-600 font-semibold">${nonCitoOver.toLocaleString()} Lewat</span>`
                    );

                    $('#cache-time').text(res.cached_at || 'Baru saja');

                    renderRows(res.cito || [], '#body-cito', '#foot-cito', 'row-cito', {
                        rajalAvg: sum.cito_rajal_avg_tat,
                        ranapAvg: sum.cito_ranap_avg_tat,
                        lainnyaAvg: sum.cito_lainnya_avg_tat,
                        overallAvg: sum.cito_avg_tat
                    });

                    renderRows(res.non_cito || [], '#body-noncito', '#foot-noncito', 'row-noncito', {
                        rajalAvg: sum.non_cito_rajal_avg_tat,
                        ranapAvg: sum.non_cito_ranap_avg_tat,
                        lainnyaAvg: sum.non_cito_lainnya_avg_tat,
                        overallAvg: sum.non_cito_avg_tat
                    });

                    applyTableFilter('.row-cito', '#filter-cito-input', '#filter-cito-status');
                    applyTableFilter('.row-noncito', '#filter-noncito-input', '#filter-noncito-status');
                },
                error: function(xhr, status, err) {
                    console.error("Laporan error:", err);
                    let errMsg = 'Gagal memuat data laporan TAT.';
                    if (status === 'timeout') {
                        errMsg = 'Waktu permintaan habis karena memproses data 1 tahun. Silakan klik "Tampilkan" kembali.';
                    }
                    $('#body-cito, #body-noncito').html(`<tr><td colspan="12" class="p-6 text-center text-rose-500 font-semibold">${errMsg}</td></tr>`);
                },
                complete: function() {
                    btnText.text('Tampilkan');
                    btn.prop('disabled', false).removeClass('opacity-60');
                }
            });
        }

        function applyTableFilter(rowSelector, inputSelector, statusSelector) {
            const query = ($(inputSelector).val() || '').toLowerCase().trim();
            const status = $(statusSelector).val() || 'all';
            let visibleNo = 1;

            $(rowSelector).each(function() {
                const nameText = $(this).find('.target-search-text').text().toLowerCase();
                const codeText = $(this).find('.target-search-code').text().toLowerCase();
                const rowCompliance = $(this).attr('data-compliance');

                const matchesQuery = query === '' || nameText.includes(query) || codeText.includes(query);
                const matchesStatus = status === 'all' || rowCompliance === status;

                if (matchesQuery && matchesStatus) {
                    $(this).show();
                    $(this).find('.row-number').text(visibleNo++);
                } else {
                    $(this).hide();
                }
            });
        }

        // Live Search & Filter CITO
        $('#filter-cito-input').on('keyup', () => applyTableFilter('.row-cito', '#filter-cito-input', '#filter-cito-status'));
        $('#filter-cito-status').on('change', () => applyTableFilter('.row-cito', '#filter-cito-input', '#filter-cito-status'));

        // Live Search & Filter Non-CITO
        $('#filter-noncito-input').on('keyup', () => applyTableFilter('.row-noncito', '#filter-noncito-input', '#filter-noncito-status'));
        $('#filter-noncito-status').on('change', () => applyTableFilter('.row-noncito', '#filter-noncito-input', '#filter-noncito-status'));

        // Initial Load
        fetchReportData(false);

        $('#search-button').on('click', () => fetchReportData(false));
        $('#refresh-button').on('click', () => fetchReportData(true));

        $('#export-excel-button').on('click', function() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            const citoSla = parseInt(citoMinsInput.val(), 10) || 120;
            const nonCitoSla = parseInt(nonCitoMinsInput.val(), 10) || 240;
            window.location.href = `{{ route('laporan.tat.export-excel', [], false) }}?start_date=${start}&end_date=${end}&cito_sla=${citoSla}&non_cito_sla=${nonCitoSla}`;
        });
    });
</script>
