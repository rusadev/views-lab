@section('title', 'Laporan Penggunaan Tabung & Spesimen')

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
                            <h1 class="text-base font-bold text-slate-900">Laporan Penggunaan Tabung & Spesimen</h1>
                            <p class="text-xs text-slate-500 mt-0.5">Pemantauan konsumsi tabung vakum sampel darah (EDTA, Serum, dll) dan spesimen laboratorium.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 text-xs">
                        <span id="cache-badge" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Cache Aktif: <span id="cache-time" class="font-mono">-</span>
                        </span>
                        <button id="refresh-button" type="button" title="Muat ulang data segar dari server LIS" class="px-2.5 py-1 text-[11px] font-semibold rounded bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-300 transition-colors flex items-center gap-1">
                            <span>Perbarui Data</span>
                        </button>
                    </div>
                </div>

                <!-- Bottom Row: Filter Controls -->
                <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Presets Segmented Control -->
                        <div class="inline-flex border border-slate-300 rounded overflow-hidden bg-slate-100">
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300" data-preset="today">Hari Ini</button>
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300" data-preset="7d">7 Hari</button>
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300" data-preset="30d">30 Hari</button>
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300 active-preset bg-white text-blue-700" data-preset="this_month">Bulan Ini</button>
                            <button type="button" class="dash-preset px-3 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors" data-preset="this_year">Tahun Ini</button>
                        </div>

                        <!-- Date Inputs -->
                        <div class="flex items-center gap-1.5 bg-slate-50 px-2 py-1 border border-slate-300 rounded">
                            <input type="date" id="start_date" name="start_date" class="h-7 px-2 bg-white border border-slate-300 rounded text-xs text-slate-800 outline-none focus:border-blue-600 font-mono">
                            <span class="text-slate-400 text-xs font-medium">s/d</span>
                            <input type="date" id="end_date" name="end_date" class="h-7 px-2 bg-white border border-slate-300 rounded text-xs text-slate-800 outline-none focus:border-blue-600 font-mono">
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button id="search-button" type="button" class="h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded border border-blue-700 transition-colors flex items-center gap-1.5">
                            <span id="search-text">Tampilkan</span>
                        </button>

                        <button id="export-excel-button" type="button" class="h-9 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded border border-emerald-700 transition-colors flex items-center gap-1.5 whitespace-nowrap">
                            <span>Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary KPI Grid (4 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-white border border-slate-200 rounded p-4">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Tabung Terpakai</span>
                    <div class="text-2xl font-black text-slate-900 font-mono mt-1" id="kpiTotalTabung">-</div>
                </div>
                <div class="bg-white border border-slate-200 rounded p-4">
                    <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Rawat Jalan</span>
                    <div class="text-2xl font-black text-blue-700 font-mono mt-1" id="kpiTotalRajal">-</div>
                </div>
                <div class="bg-white border border-slate-200 rounded p-4">
                    <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Rawat Inap</span>
                    <div class="text-2xl font-black text-emerald-700 font-mono mt-1" id="kpiTotalRanap">-</div>
                </div>
                <div class="bg-white border border-slate-200 rounded p-4">
                    <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Lainnya</span>
                    <div class="text-2xl font-black text-amber-700 font-mono mt-1" id="kpiTotalLainnya">-</div>
                </div>
            </div>

            <!-- View Mode Switcher Header Card -->
            <div class="bg-white border border-slate-200 rounded p-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-blue-50 text-blue-600 rounded">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Format Penyajian Laporan</h2>
                        <p class="text-[11px] text-slate-500">Pilih tampilan ringkasan bulanan atau rincian harian.</p>
                    </div>
                </div>
                
                <!-- View Toggle Buttons -->
                <div class="inline-flex border border-slate-300 rounded overflow-hidden bg-slate-100 p-0.5 text-xs font-semibold">
                    <button type="button" id="btn-view-bulanan" class="px-3 py-1.5 rounded bg-white text-blue-700 shadow-sm transition-all flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>Tampilan Bulanan</span>
                    </button>
                    <button type="button" id="btn-view-harian" class="px-3 py-1.5 rounded text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Tampilan Harian</span>
                    </button>
                </div>
            </div>

            <!-- ================= TAMPILAN BULANAN ================= -->
            <div id="section-view-bulanan" class="space-y-4">
                
                <!-- 1. MATRIKS TREN KONSUMSI TABUNG PER BULAN (UTAMA & PALING ATAS) -->
                <div class="bg-white border border-slate-200 rounded p-4 space-y-3">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3 border-b border-slate-200 pb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-blue-100 text-blue-800 uppercase tracking-wider">Tabel Utama</span>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Matriks Tren Konsumsi Tabung per Bulan</h3>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">Daftar jenis tabung & spesimen dengan distribusi kuantitas pemakaian per bulan sepanjang periode.</p>
                        </div>

                        <!-- Filter Controls & Actions Bar -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Service Type Segmented Filter -->
                            <div class="inline-flex border border-slate-300 rounded overflow-hidden bg-slate-100 p-0.5 text-xs font-semibold">
                                <button type="button" class="btn-service-filter px-2.5 py-1 rounded bg-white text-blue-700 shadow-sm transition-all" data-service="all">Semua Unit</button>
                                <button type="button" class="btn-service-filter px-2.5 py-1 rounded text-slate-600 hover:text-slate-900 transition-all" data-service="rajal">Rawat Jalan</button>
                                <button type="button" class="btn-service-filter px-2.5 py-1 rounded text-slate-600 hover:text-slate-900 transition-all" data-service="ranap">Rawat Inap</button>
                                <button type="button" class="btn-service-filter px-2.5 py-1 rounded text-slate-600 hover:text-slate-900 transition-all" data-service="lainnya">Lainnya</button>
                            </div>

                            <!-- Expand/Collapse Details Toggle -->
                            <button type="button" id="btn-toggle-all-details" class="px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition-colors flex items-center gap-1">
                                <span id="toggle-details-text">Perluas Rincian (+)</span>
                            </button>

                            <!-- Live Search Input -->
                            <input type="text" id="filter-monthly-tabung-input" placeholder="Cari tabung / spesimen..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-blue-600 w-48 lg:w-56">
                        </div>
                    </div>

                    <!-- Table with Months as Column Headers -->
                    <div class="overflow-x-auto">
                        <table id="tableMonthlyTabung" class="w-full text-xs text-center border-collapse border border-slate-200">
                            <thead id="tableHeadMonthlyTabung" class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200">
                                <!-- Generated Dynamic Month Headers -->
                            </thead>
                            <tbody id="tableBodyMonthlyTabung" class="divide-y divide-slate-200 text-slate-800">
                                <!-- Generated Dynamic Rows -->
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                        <span>* Tip: Klik baris tabung untuk melihat rincian per unit layanan (Rajal, Ranap, Lainnya) tiap bulan.</span>
                        <span id="monthly-table-count" class="font-mono font-semibold text-slate-700"></span>
                    </div>
                </div>

                <!-- 2. GRAFIK TREN PEMAKAIAN TABUNG PER BULAN -->
                <div class="bg-white border border-slate-200 rounded p-4">
                    <div class="border-b border-slate-200 pb-2 mb-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Grafik Tren Pemakaian Tabung per Bulan</h3>
                        <p class="text-[11px] text-slate-500">Visualisasi komparasi dinamika konsumsi jenis tabung spesimen antar bulan.</p>
                    </div>
                    <div class="w-full min-h-[280px]">
                        <canvas id="tabungMonthlyChart" style="width: 100%; height: 280px;"></canvas>
                    </div>
                </div>

                <!-- 3. REKAPITULASI KONSUMSI PER JENIS TABUNG / SPESIMEN (AUDIT RINGKAS) -->
                <div class="bg-white border border-slate-200 rounded p-4 space-y-3">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-200 pb-3">
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rekapitulasi Layanan per Jenis Tabung (Total Periode)</h3>
                            <p class="text-[11px] text-slate-500">Ringkasan total tabung yang terpakai selama periode berdasarkan unit pelayanan (Rawat Jalan, Rawat Inap, Lainnya).</p>
                        </div>
                        <input type="text" id="filter-tabung-input" placeholder="Cari rekapitulasi..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-blue-600 w-full sm:w-64">
                    </div>

                    <div class="overflow-x-auto">
                        <table id="tableSummaryTabung" class="w-full text-xs text-left border-collapse">
                            <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3 w-12 text-center">No</th>
                                    <th class="py-2.5 px-3">Jenis Tabung / Spesimen</th>
                                    <th class="py-2.5 px-3 w-28 text-right text-blue-700">Rawat Jalan</th>
                                    <th class="py-2.5 px-3 w-28 text-right text-emerald-700">Rawat Inap</th>
                                    <th class="py-2.5 px-3 w-28 text-right text-amber-700">Lainnya</th>
                                    <th class="py-2.5 px-3 w-32 text-right bg-slate-200">Total Tabung</th>
                                </tr>
                            </thead>
                            <tbody id="tableBodySummaryTabung" class="divide-y divide-slate-200 text-slate-800">
                                <!-- Skeleton / Rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- ================= TAMPILAN HARIAN ================= -->
            <div id="section-view-harian" class="space-y-4 hidden">
                <!-- Table of Tube Usage per Day -->
                <div class="bg-white border border-slate-200 rounded p-4 space-y-3">
                    <div class="border-b border-slate-200 pb-2">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Tabel Matriks Konsumsi Tabung per Hari</h3>
                        <p class="text-[11px] text-slate-500">Rincian kuantitas jenis tabung terpakai harian untuk audit persediaan logistik harian.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="tabungDailyTable" class="w-full border border-slate-200 text-xs text-center border-collapse">
                            <thead id="tableHeadDailyTabung" class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200"></thead>
                            <tbody id="tableBodyDailyTabung" class="divide-y divide-slate-200 text-slate-800">
                                <!-- Rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Chart of Tube Usage per Day -->
                <div class="bg-white border border-slate-200 rounded p-4">
                    <div class="border-b border-slate-200 pb-2 mb-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Grafik Distribusi Pemakaian Tabung Harian</h3>
                        <p class="text-[11px] text-slate-500">Proporsi jenis sampel spesimen yang masuk ke laboratorium per tanggal.</p>
                    </div>
                    <div class="w-full min-h-[260px]">
                        <canvas id="tabungDailyChart" style="width: 100%; height: 260px;"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<script>
    // Standard Medical Vacuum Tube & Specimen Color Palette (ISO 6710 / BD Vacutainer)
    function getTubeColor(name, code) {
        const n = (name || '').toLowerCase();
        const c = String(code || '').trim();

        // 1. EDTA (Ungu / Lavender / Purple - Hematologi)
        if (n.includes('edta') || c === '30') {
            return { bg: '#8b5cf6', border: '#7c3aed', label: 'Tabung Ungu (EDTA - Hematologi)' };
        }
        // 2. Serum / Clot Activator (Merah / Red - Kimia Darah / Serologi)
        if (n.includes('serum') || c === '10' || c === '16' || n.includes('clot')) {
            return { bg: '#ef4444', border: '#dc2626', label: 'Tabung Merah (Serum / Clot Activator)' };
        }
        // 3. Sitrat / Citrate (Biru Muda / Light Blue - Koagulasi PT/APTT)
        if (n.includes('sitrat') || n.includes('citrate') || c === '40') {
            return { bg: '#0ea5e9', border: '#0284c7', label: 'Tabung Biru Muda (Sitrat - Koagulasi)' };
        }
        // 4. Arteri / Heparin (Hijau / Green - Analisa Gas Darah)
        if (n.includes('arteri') || n.includes('heparin') || c === '73') {
            return { bg: '#10b981', border: '#059669', label: 'Tabung Hijau (Heparin / AGD Arteri)' };
        }
        // 5. Urin / Urine (Kuning / Yellow - Wadah Urin)
        if (n.includes('urin') || n.includes('urine') || c === '20' || c === '28' || c === '224') {
            return { bg: '#f59e0b', border: '#d97706', label: 'Wadah Kuning (Urin)' };
        }
        // 6. Faeces / Feses (Cokelat / Brown - Wadah Faeces)
        if (n.includes('faeces') || n.includes('feses') || n.includes('stool') || c === '85') {
            return { bg: '#854d0e', border: '#713f12', label: 'Wadah Cokelat (Faeces)' };
        }
        // 7. Cairan Tubuh / Pleura / Ascites / Sendi (Cyan / Teal - Non-blood Body Fluids)
        if (n.includes('cairan') || n.includes('c.') || c === '69' || c === '935' || c === '921' || c === '68') {
            return { bg: '#06b6d4', border: '#0891b2', label: 'Tabung Cyan (Cairan Tubuh)' };
        }
        // 8. Darah Lengkap / Whole Blood (Merah Gelap)
        if (n.includes('darah') || c === '901') {
            return { bg: '#b91c1c', border: '#991b1b', label: 'Merah Tua (Darah)' };
        }
        // 9. Glukosa / Fluoride (Abu-abu / Gray)
        if (n.includes('glukosa') || n.includes('fluoride') || n.includes('oxalate')) {
            return { bg: '#6b7280', border: '#4b5563', label: 'Tabung Abu-abu (Glukosa / Fluoride)' };
        }
        // 10. LED / ESR (Hitam / Black)
        if (n.includes('led') || n.includes('esr')) {
            return { bg: '#1e293b', border: '#0f172a', label: 'Tabung Hitam (LED / ESR)' };
        }
        // 11. VTM / Swab (Pink)
        if (n.includes('vtm') || n.includes('swab')) {
            return { bg: '#ec4899', border: '#db2777', label: 'Tabung Pink (VTM / Swab)' };
        }
        // Default: Netral / Abu-abu
        return { bg: '#94a3b8', border: '#64748b', label: name || 'Spesimen' };
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
            try {
                Chart.register(ChartDataLabels);
            } catch (e) {
                console.warn('Gagal mendaftarkan ChartDataLabels:', e);
            }
        }

        function formatDateLocal(d) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        const now = new Date();
        const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        if (startDateInput && !startDateInput.value) {
            startDateInput.value = formatDateLocal(startOfMonth);
        }
        if (endDateInput && !endDateInput.value) {
            endDateInput.value = formatDateLocal(now);
        }

        // Global State & Charts
        let currentReportData = null;
        let activeViewMode = 'bulanan'; // 'bulanan' or 'harian'
        let monthlyServiceFilter = 'all'; // 'all', 'rajal', 'ranap', 'lainnya'
        let allDetailsExpanded = false;
        let monthlyChart = null;
        let dailyChart = null;

        // View Mode Toggle Handler
        $('#btn-view-bulanan').on('click', function() {
            if (activeViewMode === 'bulanan') return;
            activeViewMode = 'bulanan';
            $(this).addClass('bg-white text-blue-700 shadow-sm').removeClass('text-slate-600 hover:text-slate-900');
            $('#btn-view-harian').removeClass('bg-white text-blue-700 shadow-sm').addClass('text-slate-600 hover:text-slate-900');
            $('#section-view-bulanan').removeClass('hidden');
            $('#section-view-harian').addClass('hidden');
            if (currentReportData) renderMonthlyView(currentReportData);
        });

        $('#btn-view-harian').on('click', function() {
            if (activeViewMode === 'harian') return;
            activeViewMode = 'harian';
            $(this).addClass('bg-white text-blue-700 shadow-sm').removeClass('text-slate-600 hover:text-slate-900');
            $('#btn-view-bulanan').removeClass('bg-white text-blue-700 shadow-sm').addClass('text-slate-600 hover:text-slate-900');
            $('#section-view-harian').removeClass('hidden');
            $('#section-view-bulanan').addClass('hidden');
            if (currentReportData) renderDailyView(currentReportData);
        });

        // Service Filter Segmented Control Handler
        $('.btn-service-filter').on('click', function() {
            $('.btn-service-filter').removeClass('bg-white text-blue-700 shadow-sm').addClass('text-slate-600 hover:text-slate-900');
            $(this).addClass('bg-white text-blue-700 shadow-sm').removeClass('text-slate-600 hover:text-slate-900');
            monthlyServiceFilter = $(this).data('service');
            if (currentReportData) renderMonthlyMatrixTable(currentReportData);
        });

        // Toggle Expand/Collapse All Details
        $('#btn-toggle-all-details').on('click', function() {
            allDetailsExpanded = !allDetailsExpanded;
            if (allDetailsExpanded) {
                $('#toggle-details-text').text('Ciutkan Rincian (-)');
                $('.tube-sub-row').removeClass('hidden');
                $('.expand-indicator').text('▲');
            } else {
                $('#toggle-details-text').text('Perluas Rincian (+)');
                $('.tube-sub-row').addClass('hidden');
                $('.expand-indicator').text('▼');
            }
        });

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
                } else if (preset === 'this_year') {
                    fromDate = new Date(toDate.getFullYear(), 0, 1);
                }

                if (startDateInput) startDateInput.value = formatDateLocal(fromDate);
                if (endDateInput) endDateInput.value = formatDateLocal(toDate);
                fetchReportData(false);
            });
        });

        function showSkeletons() {
            $('#kpiTotalTabung, #kpiTotalRajal, #kpiTotalRanap, #kpiTotalLainnya').html(
                '<span class="inline-block h-6 w-24 bg-slate-200 animate-pulse rounded"></span>'
            );
            
            let skelHtml = '';
            for (let i = 0; i < 5; i++) {
                skelHtml += `
                    <tr class="animate-pulse">
                        <td class="p-2.5"><div class="h-3 w-6 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2.5"><div class="h-3 w-40 bg-slate-200 rounded"></div></td>
                        <td class="p-2.5"><div class="h-3 w-12 bg-slate-200 rounded ml-auto"></div></td>
                        <td class="p-2.5"><div class="h-3 w-12 bg-slate-200 rounded ml-auto"></div></td>
                        <td class="p-2.5"><div class="h-3 w-12 bg-slate-200 rounded ml-auto"></div></td>
                        <td class="p-2.5 bg-slate-50"><div class="h-3 w-16 bg-slate-200 rounded ml-auto"></div></td>
                    </tr>
                `;
            }
            $('#tableHeadMonthlyTabung').html('<tr><th class="p-2.5">Memuat header bulan...</th></tr>');
            $('#tableBodyMonthlyTabung').html(skelHtml);
            $('#tableBodySummaryTabung').html(skelHtml);
            $('#tableHeadDailyTabung').html('<tr><th class="p-2">Tanggal</th><th class="p-2 bg-slate-200">Total</th></tr>');
            $('#tableBodyDailyTabung').html(skelHtml);
        }

        function fetchReportData(forceRefresh = false) {
            const startDate = document.getElementById('start_date')?.value || '';
            const endDate = document.getElementById('end_date')?.value || '';
            const btn = $('#search-button');
            const btnText = $('#search-text');

            showSkeletons();
            btnText.text('Memuat...');
            btn.prop('disabled', true).addClass('opacity-60');

            $.ajax({
                url: "{{ route('laporan.penggunaan-tabung.data', [], false) }}",
                type: "GET",
                timeout: 180000,
                data: { 
                    start_date: startDate, 
                    end_date: endDate,
                    refresh: forceRefresh ? 1 : 0
                },
                success: function(res) {
                    try {
                        currentReportData = res;
                        $('#cache-time').text(res.cached_at || 'Baru saja');

                        // Update KPI Cards
                        const kpi = res.kpi || { total: res.total_keseluruhan || 0, rajal: 0, ranap: 0, lainnya: 0 };
                        $('#kpiTotalTabung').text((kpi.total || 0).toLocaleString());
                        $('#kpiTotalRajal').text((kpi.rajal || 0).toLocaleString());
                        $('#kpiTotalRanap').text((kpi.ranap || 0).toLocaleString());
                        $('#kpiTotalLainnya').text((kpi.lainnya || 0).toLocaleString());

                        // Render based on active view
                        if (activeViewMode === 'bulanan') {
                            renderMonthlyView(res);
                        } else {
                            renderDailyView(res);
                        }
                    } catch (renderErr) {
                        console.error("Gagal memproses data laporan:", renderErr);
                        $('#tableBodyMonthlyTabung').html(`<tr><td colspan="10" class="p-4 text-center text-rose-500 text-xs font-semibold">Terjadi kesalahan visualisasi tabel: ${renderErr.message}</td></tr>`);
                    }
                },
                error: function(xhr, status, err) {
                    console.error("Laporan error:", status, err, xhr.responseText);
                    const errorMsg = (xhr.status === 0) 
                        ? 'Koneksi ke server terputus atau timeout. Pastikan koneksi aktif.' 
                        : (xhr.responseJSON?.message || 'Gagal memuat data laporan dari server. Silakan klik Perbarui Data.');
                    $('#tableBodyMonthlyTabung').html(`<tr><td colspan="10" class="p-4 text-center text-rose-500 text-xs font-semibold">${errorMsg}</td></tr>`);
                    $('#tableBodySummaryTabung').html(`<tr><td colspan="6" class="p-4 text-center text-rose-500 text-xs font-semibold">${errorMsg}</td></tr>`);
                    $('#kpiTotalTabung, #kpiTotalRajal, #kpiTotalRanap, #kpiTotalLainnya').text('-');
                },
                complete: function() {
                    btnText.text('Tampilkan');
                    btn.prop('disabled', false).removeClass('opacity-60');
                }
            });
        }

        // ================= RENDERING: TAMPILAN BULANAN =================
        function renderMonthlyView(res) {
            renderMonthlyMatrixTable(res);
            renderMonthlyChart(res);
            renderSummaryTable(res);
        }

        // 1. Matriks Utama: Bulan sebagai Header Kolom & Tabung sebagai Baris (Dengan Kotak Warna Standar)
        function renderMonthlyMatrixTable(res) {
            const tubeMatrix = res.monthly_matrix || [];
            const monthList = res.month_list || [];

            // A. Table Header (Bulan Horizontal, Tanpa Kode)
            let thHtml = `
                <tr>
                    <th class="py-2.5 px-3 w-12 text-center">No</th>
                    <th class="py-2.5 px-3 text-left min-w-[220px]">Jenis Tabung / Spesimen</th>
            `;
            monthList.forEach(m => {
                thHtml += `<th class="py-2.5 px-3 text-center min-w-[95px]">${m.label}</th>`;
            });
            thHtml += `
                    <th class="py-2.5 px-3 text-right w-24 text-blue-700">Rajal</th>
                    <th class="py-2.5 px-3 text-right w-24 text-emerald-700">Ranap</th>
                    <th class="py-2.5 px-3 text-right w-24 text-amber-700">Lainnya</th>
                    <th class="py-2.5 px-3 text-right w-28 bg-slate-200">Total Tabung</th>
                </tr>
            `;
            $('#tableHeadMonthlyTabung').html(thHtml);

            // B. Table Body (Rows per Tube + Sub-rows Accordion per Layanan)
            let tbHtml = '';
            let no = 1;
            const monthSums = {};
            monthList.forEach(m => {
                monthSums[m.key] = { rajal: 0, ranap: 0, lainnya: 0, total: 0 };
            });
            let grandRajal = 0, grandRanap = 0, grandLainnya = 0, grandTotal = 0;

            if (tubeMatrix.length === 0) {
                tbHtml = `<tr><td colspan="${monthList.length + 6}" class="p-6 text-center text-slate-400">Tidak ada data penggunaan tabung pada periode ini.</td></tr>`;
            } else {
                tubeMatrix.forEach(tube => {
                    grandRajal += tube.total_rajal;
                    grandRanap += tube.total_ranap;
                    grandLainnya += tube.total_lainnya;
                    grandTotal += tube.total_all;

                    const tubeColor = getTubeColor(tube.name, tube.code);

                    // Build cells for each month
                    let rowMonthCells = '';
                    let subRajalCells = '';
                    let subRanapCells = '';
                    let subLainnyaCells = '';

                    monthList.forEach(m => {
                        const mVal = tube.months[m.key] || { rajal: 0, ranap: 0, lainnya: 0, total: 0 };
                        monthSums[m.key].rajal += mVal.rajal;
                        monthSums[m.key].ranap += mVal.ranap;
                        monthSums[m.key].lainnya += mVal.lainnya;
                        monthSums[m.key].total += mVal.total;

                        let dispVal = 0;
                        if (monthlyServiceFilter === 'all') dispVal = mVal.total;
                        else if (monthlyServiceFilter === 'rajal') dispVal = mVal.rajal;
                        else if (monthlyServiceFilter === 'ranap') dispVal = mVal.ranap;
                        else if (monthlyServiceFilter === 'lainnya') dispVal = mVal.lainnya;

                        rowMonthCells += `<td class="py-2 px-3 text-center font-mono ${dispVal === 0 ? 'text-slate-300' : 'font-semibold text-slate-800'}">${dispVal.toLocaleString()}</td>`;

                        // Sub-row cells
                        subRajalCells += `<td class="py-1 px-3 text-center font-mono ${mVal.rajal === 0 ? 'text-slate-300' : 'text-blue-700 font-medium'}">${mVal.rajal.toLocaleString()}</td>`;
                        subRanapCells += `<td class="py-1 px-3 text-center font-mono ${mVal.ranap === 0 ? 'text-slate-300' : 'text-emerald-700 font-medium'}">${mVal.ranap.toLocaleString()}</td>`;
                        subLainnyaCells += `<td class="py-1 px-3 text-center font-mono ${mVal.lainnya === 0 ? 'text-slate-300' : 'text-amber-700 font-medium'}">${mVal.lainnya.toLocaleString()}</td>`;
                    });

                    // Main Tube Row (Kotak Warna Tabung Standar)
                    tbHtml += `
                        <tr class="hover:bg-slate-50 cursor-pointer tube-main-row transition-colors" data-tube-code="${tube.code}">
                            <td class="py-2.5 px-3 text-center text-slate-400 font-mono">${no++}</td>
                            <td class="py-2.5 px-3 text-left font-semibold text-slate-900 search-tube-target">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-3.5 h-3.5 rounded-sm shadow-xs border shrink-0" 
                                              style="background-color: ${tubeColor.bg}; border-color: ${tubeColor.border};" 
                                              title="${tubeColor.label}"></span>
                                        <span class="truncate font-semibold text-slate-800">${tube.name}</span>
                                    </div>
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] expand-indicator font-mono transition-transform shrink-0" title="Klik untuk rincian unit">${allDetailsExpanded ? '▲' : '▼'}</span>
                                </div>
                            </td>
                            ${rowMonthCells}
                            <td class="py-2.5 px-3 text-right font-mono text-blue-700 font-medium">${tube.total_rajal.toLocaleString()}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-emerald-700 font-medium">${tube.total_ranap.toLocaleString()}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-amber-700 font-medium">${tube.total_lainnya.toLocaleString()}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-black bg-slate-50">${tube.total_all.toLocaleString()}</td>
                        </tr>
                    `;

                    // Sub-rows: Rajal, Ranap, Lainnya
                    tbHtml += `
                        <tr class="tube-sub-row tube-sub-${tube.code} bg-blue-50/40 text-[11px] border-l-2 border-blue-500 ${allDetailsExpanded ? '' : 'hidden'}">
                            <td></td>
                            <td class="py-1 px-3 text-left pl-8 text-blue-700 font-semibold flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 inline-block"></span>
                                <span>Rawat Jalan</span>
                            </td>
                            ${subRajalCells}
                            <td class="py-1 px-3 text-right font-mono text-blue-700 font-bold">${tube.total_rajal.toLocaleString()}</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr class="tube-sub-row tube-sub-${tube.code} bg-emerald-50/40 text-[11px] border-l-2 border-emerald-500 ${allDetailsExpanded ? '' : 'hidden'}">
                            <td></td>
                            <td class="py-1 px-3 text-left pl-8 text-emerald-700 font-semibold flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                <span>Rawat Inap</span>
                            </td>
                            ${subRanapCells}
                            <td></td>
                            <td class="py-1 px-3 text-right font-mono text-emerald-700 font-bold">${tube.total_ranap.toLocaleString()}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr class="tube-sub-row tube-sub-${tube.code} bg-amber-50/40 text-[11px] border-l-2 border-amber-500 ${allDetailsExpanded ? '' : 'hidden'}">
                            <td></td>
                            <td class="py-1 px-3 text-left pl-8 text-amber-700 font-semibold flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span>
                                <span>Lainnya (MCU / Luar)</span>
                            </td>
                            ${subLainnyaCells}
                            <td colspan="2"></td>
                            <td class="py-1 px-3 text-right font-mono text-amber-700 font-bold">${tube.total_lainnya.toLocaleString()}</td>
                            <td></td>
                        </tr>
                    `;
                });

                // Total Summary Row
                let mSumCells = '';
                monthList.forEach(m => {
                    let sVal = 0;
                    if (monthlyServiceFilter === 'all') sVal = monthSums[m.key].total;
                    else if (monthlyServiceFilter === 'rajal') sVal = monthSums[m.key].rajal;
                    else if (monthlyServiceFilter === 'ranap') sVal = monthSums[m.key].ranap;
                    else if (monthlyServiceFilter === 'lainnya') sVal = monthSums[m.key].lainnya;

                    mSumCells += `<td class="py-2.5 px-3 text-center font-mono font-black">${sVal.toLocaleString()}</td>`;
                });

                tbHtml += `
                    <tr class="bg-slate-100 font-black border-t-2 border-slate-300">
                        <td colspan="2" class="py-2.5 px-3 text-left font-bold">TOTAL PENGGUNAAN</td>
                        ${mSumCells}
                        <td class="py-2.5 px-3 text-right font-mono text-blue-700">${grandRajal.toLocaleString()}</td>
                        <td class="py-2.5 px-3 text-right font-mono text-emerald-700">${grandRanap.toLocaleString()}</td>
                        <td class="py-2.5 px-3 text-right font-mono text-amber-700">${grandLainnya.toLocaleString()}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-black bg-slate-200">${grandTotal.toLocaleString()}</td>
                    </tr>
                `;
            }

            $('#tableBodyMonthlyTabung').html(tbHtml);
            $('#monthly-table-count').text(`${tubeMatrix.length} Jenis Spesimen`);

            // Row click handler to toggle sub-rows
            $('.tube-main-row').off('click').on('click', function() {
                const code = $(this).data('tube-code');
                const subRows = $(`.tube-sub-${code}`);
                const indicator = $(this).find('.expand-indicator');
                if (subRows.hasClass('hidden')) {
                    subRows.removeClass('hidden');
                    indicator.text('▲');
                } else {
                    subRows.addClass('hidden');
                    indicator.text('▼');
                }
            });
        }

        // 2. Grafik Tren Bulanan (Dataset Mengikuti Warna Tabung Standar)
        function renderMonthlyChart(res) {
            const summaryList = res.summary_tabung || [];
            const monthlySummary = res.monthly_summary || [];
            const sampleNames = summaryList.map(s => s.sample_name);
            const chartLabels = monthlySummary.map(m => m.label);

            const chartDatasets = summaryList.map(s => {
                const tColor = getTubeColor(s.sample_name, s.sample_code);
                return {
                    label: s.sample_name,
                    data: monthlySummary.map(m => m.samples[s.sample_name] || 0),
                    backgroundColor: tColor.bg,
                    borderColor: tColor.border,
                    borderWidth: 1,
                    borderRadius: 4
                };
            });

            if (monthlyChart) {
                monthlyChart.destroy();
            }

            if (typeof Chart === 'undefined') return;
            const chartPlugins = (typeof ChartDataLabels !== 'undefined') ? [ChartDataLabels] : [];

            const ctxMonthly = document.getElementById('tabungMonthlyChart');
            if (ctxMonthly) {
                try {
                    monthlyChart = new Chart(ctxMonthly, {
                        type: 'bar',
                        plugins: chartPlugins,
                        data: {
                            labels: chartLabels,
                            datasets: chartDatasets
                        },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { top: 22 } },
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 10 } } },
                            datalabels: {
                                display: (ctx) => ctx.dataset.data[ctx.dataIndex] > 0,
                                color: '#0f172a',
                                anchor: 'end',
                                align: 'top',
                                offset: 2,
                                font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 10 },
                                formatter: (v) => v
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } } },
                            y: { beginAtZero: true, grace: '15%', grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } } }
                        }
                    }
                });
                } catch (e) {
                    console.warn('Gagal merender grafik bulanan:', e);
                }
            }
        }

        // 3. Tabel Rekapitulasi Ringkas Layanan (Bawah Chart, Tanpa Kode)
        function renderSummaryTable(res) {
            const summaryList = res.summary_tabung || [];
            let sumHtml = '';
            let sumRajal = 0, sumRanap = 0, sumLainnya = 0, sumTotal = 0;
            let no = 1;

            if (summaryList.length === 0) {
                sumHtml = '<tr><td colspan="6" class="p-4 text-center text-slate-400">Tidak ada data penggunaan tabung pada periode ini.</td></tr>';
            } else {
                summaryList.forEach(row => {
                    const r = parseInt(row.total_rajal || 0);
                    const inP = parseInt(row.total_ranap || 0);
                    const l = parseInt(row.total_lainnya || 0);
                    const tot = parseInt(row.total_keseluruhan || 0);
                    const tubeColor = getTubeColor(row.sample_name, row.sample_code);

                    sumRajal += r;
                    sumRanap += inP;
                    sumLainnya += l;
                    sumTotal += tot;

                    sumHtml += `
                        <tr class="hover:bg-slate-50 summary-row-item">
                            <td class="py-2.5 px-3 text-center text-slate-400 font-mono">${no++}</td>
                            <td class="py-2.5 px-3 text-left font-semibold text-slate-900 tabung-name-target">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-3.5 h-3.5 rounded-sm shadow-xs border shrink-0" 
                                          style="background-color: ${tubeColor.bg}; border-color: ${tubeColor.border};" 
                                          title="${tubeColor.label}"></span>
                                    <span>${row.sample_name}</span>
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-blue-700">${r.toLocaleString()}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-emerald-700">${inP.toLocaleString()}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-amber-700">${l.toLocaleString()}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold bg-slate-50">${tot.toLocaleString()}</td>
                        </tr>
                    `;
                });

                // Summary Total Row
                sumHtml += `
                    <tr class="bg-slate-100 font-black border-t-2 border-slate-300">
                        <td colspan="2" class="py-2.5 px-3 text-left">TOTAL KESELURUHAN</td>
                        <td class="py-2.5 px-3 text-right font-mono text-blue-700">${sumRajal.toLocaleString()}</td>
                        <td class="py-2.5 px-3 text-right font-mono text-emerald-700">${sumRanap.toLocaleString()}</td>
                        <td class="py-2.5 px-3 text-right font-mono text-amber-700">${sumLainnya.toLocaleString()}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-black bg-slate-200">${sumTotal.toLocaleString()}</td>
                    </tr>
                `;
            }
            $('#tableBodySummaryTabung').html(sumHtml);
        }

        // ================= RENDERING: TAMPILAN HARIAN =================
        function renderDailyView(res) {
            const daily = res.daily || { samples: [], data: [] };
            const samples = daily.samples || [];
            const data = daily.data || [];

            // 1. Table Header
            let thHtml = `<tr><th class="p-2 text-left">Tanggal</th>`;
            samples.forEach(s => thHtml += `<th class="p-2">${s}</th>`);
            thHtml += `<th class="p-2 bg-slate-200">Total</th></tr>`;
            $('#tableHeadDailyTabung').html(thHtml);

            // 2. Table Body
            let tbHtml = '';
            if (data.length === 0) {
                tbHtml = `<tr><td colspan="${samples.length + 2}" class="p-4 text-center text-slate-400">Tidak ada data harian pada periode ini.</td></tr>`;
            } else {
                data.forEach(row => {
                    let cells = '';
                    samples.forEach(s => {
                        const val = row[s] || 0;
                        cells += `<td class="p-2 text-center font-mono ${val === 0 ? 'text-slate-300' : 'text-slate-800 font-medium'}">${val.toLocaleString()}</td>`;
                    });
                    tbHtml += `
                        <tr class="hover:bg-slate-50">
                            <td class="p-2 font-mono text-slate-700 font-medium">${row.tanggal}</td>
                            ${cells}
                            <td class="p-2 text-center font-mono font-bold bg-slate-50">${row.total.toLocaleString()}</td>
                        </tr>
                    `;
                });
            }
            $('#tableBodyDailyTabung').html(tbHtml);

            // 3. Daily Chart
            const dates = data.map(d => d.tanggal);
            const palette = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#db2777', '#0891b2', '#4b5563'];

            const datasets = samples.map((sample, idx) => ({
                label: sample,
                data: data.map(d => d[sample] || 0),
                backgroundColor: palette[idx % palette.length],
                borderRadius: 3
            }));

            if (dailyChart) {
                dailyChart.destroy();
            }

            if (typeof Chart === 'undefined') return;
            const chartPlugins = (typeof ChartDataLabels !== 'undefined') ? [ChartDataLabels] : [];

            const ctxDaily = document.getElementById('tabungDailyChart');
            if (ctxDaily) {
                try {
                    dailyChart = new Chart(ctxDaily, {
                        type: 'bar',
                        plugins: chartPlugins,
                        data: {
                            labels: dates,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: { padding: { top: 20 } },
                            plugins: {
                                legend: { position: 'top', labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 10 } } },
                                datalabels: {
                                    display: (ctx) => ctx.dataset.data[ctx.dataIndex] > 0,
                                    color: '#0f172a',
                                    anchor: 'end',
                                    align: 'top',
                                    offset: 2,
                                    font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 10 },
                                    formatter: (v) => v
                                }
                            },
                            scales: {
                                x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } } },
                                y: { beginAtZero: true, grace: '15%', grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } } }
                            }
                        }
                    });
                } catch (e) {
                    console.warn('Gagal merender grafik harian:', e);
                }
            }
        }

        // Live search on Main Monthly Matrix Table
        $('#filter-monthly-tabung-input').on('keyup', function() {
            const query = $(this).val().toLowerCase();
            $('.tube-main-row').each(function() {
                const name = $(this).find('.search-tube-target').text().toLowerCase();
                const tubeCode = $(this).data('tube-code');
                const subRows = $(`.tube-sub-${tubeCode}`);

                if (name.includes(query)) {
                    $(this).show();
                    if (allDetailsExpanded) subRows.removeClass('hidden');
                } else {
                    $(this).hide();
                    subRows.addClass('hidden');
                }
            });
        });

        // Live search on Summary Reference Table
        $('#filter-tabung-input').on('keyup', function() {
            const query = $(this).val().toLowerCase();
            $('.summary-row-item').each(function() {
                const text = $(this).find('.tabung-name-target').text().toLowerCase();
                if (text.includes(query)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Initialize Data Load
        fetchReportData(false);

        $('#search-button').on('click', () => fetchReportData(false));
        $('#refresh-button').on('click', () => fetchReportData(true));

        // Excel Export Trigger
        $('#export-excel-button').on('click', function() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            window.location.href = `{{ route('laporan.penggunaan-tabung.export-excel', [], false) }}?start_date=${start}&end_date=${end}`;
        });
    });
</script>