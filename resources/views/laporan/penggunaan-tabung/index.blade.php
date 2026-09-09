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
                <!-- 1. Rekapitulasi Jenis Tabung per Layanan -->
                <div class="bg-white border border-slate-200 rounded p-4 space-y-3">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-200 pb-3">
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rekapitulasi Konsumsi per Jenis Tabung / Spesimen</h3>
                            <p class="text-[11px] text-slate-500">Volume pemakaian tabung per kategori spesimen dan unit pelayanan (Rawat Jalan, Rawat Inap, Lainnya).</p>
                        </div>
                        <input type="text" id="filter-tabung-input" placeholder="Cari jenis tabung/spesimen..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-blue-600 w-full sm:w-64">
                    </div>

                    <div class="overflow-x-auto">
                        <table id="tableSummaryTabung" class="w-full text-xs text-left border-collapse">
                            <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3 w-12 text-center">No</th>
                                    <th class="py-2.5 px-3 w-28 text-center">Kode Spesimen</th>
                                    <th class="py-2.5 px-3">Jenis Tabung / Spesimen</th>
                                    <th class="py-2.5 px-3 w-28 text-right">Rawat Jalan</th>
                                    <th class="py-2.5 px-3 w-28 text-right">Rawat Inap</th>
                                    <th class="py-2.5 px-3 w-28 text-right">Lainnya</th>
                                    <th class="py-2.5 px-3 w-32 text-right bg-slate-200">Total Tabung</th>
                                </tr>
                            </thead>
                            <tbody id="tableBodySummaryTabung" class="divide-y divide-slate-200 text-slate-800">
                                <!-- Skeleton / Rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Rincian Konsumsi Tabung per Bulan -->
                <div class="bg-white border border-slate-200 rounded p-4 space-y-3">
                    <div class="border-b border-slate-200 pb-2">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rincian Tren Konsumsi per Bulan</h3>
                        <p class="text-[11px] text-slate-500">Distribusi volume penggunaan tabung dan spesimen per bulan sepanjang rentang waktu laporan.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table id="tableMonthlyTabung" class="w-full text-xs text-center border-collapse border border-slate-200">
                            <thead id="tableHeadMonthlyTabung" class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200">
                                <!-- Generated Dynamic Headers -->
                            </thead>
                            <tbody id="tableBodyMonthlyTabung" class="divide-y divide-slate-200 text-slate-800">
                                <!-- Generated Dynamic Rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. Grafik Tren Pemakaian Tabung per Bulan -->
                <div class="bg-white border border-slate-200 rounded p-4">
                    <div class="border-b border-slate-200 pb-2 mb-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Grafik Tren Pemakaian Tabung per Bulan</h3>
                        <p class="text-[11px] text-slate-500">Perbandingan pergerakan volume konsumsi jenis tabung utama tiap bulan.</p>
                    </div>
                    <div class="w-full min-h-[280px]">
                        <canvas id="tabungMonthlyChart" style="width: 100%; height: 280px;"></canvas>
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
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof ChartDataLabels !== 'undefined') {
            Chart.register(ChartDataLabels);
        }

        const now = new Date();
        const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
        document.getElementById('start_date').value = startOfMonth.toISOString().split('T')[0];
        document.getElementById('end_date').value = now.toISOString().split('T')[0];

        // Global State & Charts
        let currentReportData = null;
        let activeViewMode = 'bulanan'; // 'bulanan' or 'harian'
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

                document.getElementById('start_date').value = fromDate.toISOString().split('T')[0];
                document.getElementById('end_date').value = toDate.toISOString().split('T')[0];
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
                        <td class="p-2.5"><div class="h-3 w-16 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2.5"><div class="h-3 w-40 bg-slate-200 rounded"></div></td>
                        <td class="p-2.5"><div class="h-3 w-12 bg-slate-200 rounded ml-auto"></div></td>
                        <td class="p-2.5"><div class="h-3 w-12 bg-slate-200 rounded ml-auto"></div></td>
                        <td class="p-2.5"><div class="h-3 w-12 bg-slate-200 rounded ml-auto"></div></td>
                        <td class="p-2.5 bg-slate-50"><div class="h-3 w-16 bg-slate-200 rounded ml-auto"></div></td>
                    </tr>
                `;
            }
            $('#tableBodySummaryTabung').html(skelHtml);
            $('#tableHeadMonthlyTabung').html('<tr><th class="p-2.5">Memuat header...</th></tr>');
            $('#tableBodyMonthlyTabung').html(skelHtml);
            $('#tableHeadDailyTabung').html('<tr><th class="p-2">Tanggal</th><th class="p-2 bg-slate-200">Total</th></tr>');
            $('#tableBodyDailyTabung').html(skelHtml);
        }

        function fetchReportData(forceRefresh = false) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
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
                    currentReportData = res;
                    $('#cache-time').text(res.cached_at || 'Baru saja');

                    // Update KPI Cards
                    const kpi = res.kpi || { total: res.total_keseluruhan || 0, rajal: 0, ranap: 0, lainnya: 0 };
                    $('#kpiTotalTabung').text(kpi.total.toLocaleString());
                    $('#kpiTotalRajal').text(kpi.rajal.toLocaleString());
                    $('#kpiTotalRanap').text(kpi.ranap.toLocaleString());
                    $('#kpiTotalLainnya').text(kpi.lainnya.toLocaleString());

                    // Render based on active view
                    if (activeViewMode === 'bulanan') {
                        renderMonthlyView(res);
                    } else {
                        renderDailyView(res);
                    }
                },
                error: function(xhr, status, err) {
                    console.error("Laporan error:", err);
                    $('#tableBodySummaryTabung').html('<tr><td colspan="7" class="p-4 text-center text-rose-500 text-xs">Gagal memuat data laporan dari server.</td></tr>');
                },
                complete: function() {
                    btnText.text('Tampilkan');
                    btn.prop('disabled', false).removeClass('opacity-60');
                }
            });
        }

        // ================= RENDERING: TAMPILAN BULANAN =================
        function renderMonthlyView(res) {
            const summaryList = res.summary_tabung || [];
            const monthList = res.month_list || [];
            const monthlySummary = res.monthly_summary || [];

            // 1. Render Table Summary per Specimen (Sheet 1 equivalent)
            let sumHtml = '';
            let sumRajal = 0, sumRanap = 0, sumLainnya = 0, sumTotal = 0;
            let no = 1;

            if (summaryList.length === 0) {
                sumHtml = '<tr><td colspan="7" class="p-4 text-center text-slate-400">Tidak ada data penggunaan tabung pada periode ini.</td></tr>';
            } else {
                summaryList.forEach(row => {
                    const r = parseInt(row.total_rajal || 0);
                    const inP = parseInt(row.total_ranap || 0);
                    const l = parseInt(row.total_lainnya || 0);
                    const tot = parseInt(row.total_keseluruhan || 0);

                    sumRajal += r;
                    sumRanap += inP;
                    sumLainnya += l;
                    sumTotal += tot;

                    sumHtml += `
                        <tr class="hover:bg-slate-50 summary-row-item">
                            <td class="py-2.5 px-3 text-center text-slate-400">${no++}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-semibold text-slate-600">${row.sample_code}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-900 tabung-name-target">${row.sample_name}</td>
                            <td class="py-2.5 px-3 text-right font-mono">${r.toLocaleString()}</td>
                            <td class="py-2.5 px-3 text-right font-mono">${inP.toLocaleString()}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-amber-700">${l.toLocaleString()}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold bg-slate-50">${tot.toLocaleString()}</td>
                        </tr>
                    `;
                });

                // Summary Total Row
                sumHtml += `
                    <tr class="bg-slate-100 font-black border-t-2 border-slate-300">
                        <td colspan="3" class="py-2.5 px-3 text-left">TOTAL PENGGUNAAN</td>
                        <td class="py-2.5 px-3 text-right font-mono">${sumRajal.toLocaleString()}</td>
                        <td class="py-2.5 px-3 text-right font-mono">${sumRanap.toLocaleString()}</td>
                        <td class="py-2.5 px-3 text-right font-mono text-amber-700">${sumLainnya.toLocaleString()}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-black bg-slate-200">${sumTotal.toLocaleString()}</td>
                    </tr>
                `;
            }
            $('#tableBodySummaryTabung').html(sumHtml);

            // 2. Render Table Monthly Breakdown Matrix
            const sampleNames = summaryList.map(s => s.sample_name);
            let mHeadHtml = `
                <tr>
                    <th class="py-2 px-3 text-left w-36">Bulan</th>
            `;
            sampleNames.forEach(s => {
                mHeadHtml += `<th class="py-2 px-3">${s}</th>`;
            });
            mHeadHtml += `
                    <th class="py-2 px-3 text-right w-24">Rajal</th>
                    <th class="py-2 px-3 text-right w-24">Ranap</th>
                    <th class="py-2 px-3 text-right w-24">Lainnya</th>
                    <th class="py-2 px-3 text-right w-28 bg-slate-200">Total</th>
                </tr>
            `;
            $('#tableHeadMonthlyTabung').html(mHeadHtml);

            let mBodyHtml = '';
            const sampleColSums = {};
            sampleNames.forEach(s => sampleColSums[s] = 0);
            let mSumRajal = 0, mSumRanap = 0, mSumLainnya = 0, mSumTotal = 0;

            if (monthlySummary.length === 0) {
                mBodyHtml = `<tr><td colspan="${sampleNames.length + 5}" class="p-4 text-center text-slate-400">Tidak ada data bulanan.</td></tr>`;
            } else {
                monthlySummary.forEach(mRow => {
                    mSumRajal += mRow.rajal;
                    mSumRanap += mRow.ranap;
                    mSumLainnya += mRow.lainnya;
                    mSumTotal += mRow.total;

                    mBodyHtml += `<tr class="hover:bg-slate-50">`;
                    mBodyHtml += `<td class="py-2 px-3 text-left font-bold text-slate-800">${mRow.label}</td>`;

                    sampleNames.forEach(s => {
                        const val = mRow.samples[s] || 0;
                        sampleColSums[s] += val;
                        mBodyHtml += `<td class="py-2 px-3 font-mono ${val === 0 ? 'text-slate-300' : 'text-slate-800'}">${val.toLocaleString()}</td>`;
                    });

                    mBodyHtml += `<td class="py-2 px-3 text-right font-mono">${mRow.rajal.toLocaleString()}</td>`;
                    mBodyHtml += `<td class="py-2 px-3 text-right font-mono">${mRow.ranap.toLocaleString()}</td>`;
                    mBodyHtml += `<td class="py-2 px-3 text-right font-mono text-amber-700">${mRow.lainnya.toLocaleString()}</td>`;
                    mBodyHtml += `<td class="py-2 px-3 text-right font-mono font-bold bg-slate-50">${mRow.total.toLocaleString()}</td>`;
                    mBodyHtml += `</tr>`;
                });

                // Monthly Total Row
                mBodyHtml += `
                    <tr class="bg-slate-100 font-black border-t-2 border-slate-300">
                        <td class="py-2 px-3 text-left">TOTAL</td>
                `;
                sampleNames.forEach(s => {
                    mBodyHtml += `<td class="py-2 px-3 font-mono">${sampleColSums[s].toLocaleString()}</td>`;
                });
                mBodyHtml += `
                        <td class="py-2 px-3 text-right font-mono">${mSumRajal.toLocaleString()}</td>
                        <td class="py-2 px-3 text-right font-mono">${mSumRanap.toLocaleString()}</td>
                        <td class="py-2 px-3 text-right font-mono text-amber-700">${mSumLainnya.toLocaleString()}</td>
                        <td class="py-2 px-3 text-right font-mono font-black bg-slate-200">${mSumTotal.toLocaleString()}</td>
                    </tr>
                `;
            }
            $('#tableBodyMonthlyTabung').html(mBodyHtml);

            // 3. Render Monthly Chart
            const chartLabels = monthlySummary.map(m => m.label);
            const palette = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#db2777', '#0891b2', '#4b5563', '#ea580c', '#14b8a6'];

            const chartDatasets = sampleNames.map((sName, idx) => ({
                label: sName,
                data: monthlySummary.map(m => m.samples[sName] || 0),
                backgroundColor: palette[idx % palette.length],
                borderRadius: 4
            }));

            if (monthlyChart) {
                monthlyChart.destroy();
            }

            const ctxMonthly = document.getElementById('tabungMonthlyChart');
            if (ctxMonthly) {
                monthlyChart = new Chart(ctxMonthly, {
                    type: 'bar',
                    plugins: [ChartDataLabels],
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
            }
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
            const sampleSums = {};
            samples.forEach(s => sampleSums[s] = 0);
            let sumTotal = 0;

            if (data.length === 0) {
                tbHtml = `<tr><td colspan="${samples.length + 2}" class="p-4 text-center text-slate-400">Tidak ada data harian pada periode ini.</td></tr>`;
            } else {
                data.forEach(row => {
                    tbHtml += `<tr class="hover:bg-slate-50">`;
                    tbHtml += `<td class="p-2 text-left font-semibold text-slate-800 font-mono">${row.tanggal}</td>`;
                    samples.forEach(s => {
                        const val = row[s] || 0;
                        sampleSums[s] += val;
                        tbHtml += `<td class="p-2 font-mono ${val === 0 ? 'text-slate-300' : ''}">${val.toLocaleString()}</td>`;
                    });
                    tbHtml += `<td class="p-2 font-bold bg-slate-50 font-mono">${row.total.toLocaleString()}</td>`;
                    tbHtml += `</tr>`;
                    sumTotal += row.total;
                });

                // Summary Row
                tbHtml += `<tr class="bg-slate-100 font-black border-t-2 border-slate-300">`;
                tbHtml += `<td class="p-2 text-left">TOTAL</td>`;
                samples.forEach(s => {
                    tbHtml += `<td class="p-2 font-mono">${sampleSums[s].toLocaleString()}</td>`;
                });
                tbHtml += `<td class="p-2 bg-slate-200 font-mono">${sumTotal.toLocaleString()}</td>`;
                tbHtml += `</tr>`;
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

            const ctxDaily = document.getElementById('tabungDailyChart');
            if (ctxDaily) {
                dailyChart = new Chart(ctxDaily, {
                    type: 'bar',
                    plugins: [ChartDataLabels],
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
            }
        }

        // Live table search for summary table
        $('#filter-tabung-input').on('keyup', function() {
            const query = $(this).val().toLowerCase();
            $('.summary-row-item').each(function() {
                const text = $(this).find('.tabung-name-target').text().toLowerCase();
                const code = $(this).find('td:nth-child(2)').text().toLowerCase();
                if (text.includes(query) || code.includes(query)) {
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