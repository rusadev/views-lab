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
                            <p class="text-xs text-slate-500 mt-0.5">Analisis efisiensi kecepatan pelayanan pengujian CITO (&le; 60m) dan Rutin (&le; 120m) sesuai Standar Kemenkes RI.</p>
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
                        <div class="text-[11px] text-slate-500 mt-0.5">Rata-rata TAT: <span id="kpiAvgCito" class="font-bold text-slate-700">-</span></div>
                    </div>
                </div>

                <!-- KPI 2: Kepatuhan Standar Kemenkes CITO -->
                <div class="bg-white border border-rose-200 rounded p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-rose-900 uppercase tracking-wider">Kepatuhan Kemenkes CITO</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-rose-100 text-rose-800">&le; 60 Menit</span>
                    </div>
                    <div class="mt-2" id="kpi-cito-sla-val">
                        <div class="text-2xl font-black text-rose-700 font-mono" id="kpiSlaCito">-</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Standar INM Kemenkes RI</div>
                    </div>
                </div>

                <!-- KPI 3: Total NON-CITO -->
                <div class="bg-white border border-blue-200 rounded p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-blue-900 uppercase tracking-wider">Volume Uji Rutin</span>
                        <span class="px-1.5 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-800 rounded">Non-CITO</span>
                    </div>
                    <div class="mt-2" id="kpi-noncito-val">
                        <div class="text-2xl font-black text-blue-700 font-mono" id="kpiTotalNonCito">-</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Rata-rata TAT: <span id="kpiAvgNonCito" class="font-bold text-slate-700">-</span></div>
                    </div>
                </div>

                <!-- KPI 4: Kepatuhan Standar Kemenkes NON-CITO -->
                <div class="bg-white border border-blue-200 rounded p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-blue-900 uppercase tracking-wider">Kepatuhan Kemenkes Rutin</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-blue-100 text-blue-800">&le; 120 Menit</span>
                    </div>
                    <div class="mt-2" id="kpi-noncito-sla-val">
                        <div class="text-2xl font-black text-blue-700 font-mono" id="kpiSlaNonCito">-</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">Standar INM Kemenkes RI</div>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: TAT CITO -->
            <div class="bg-white border border-slate-200 rounded p-4 space-y-3">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-200 pb-3">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">TAT CITO - Berdasarkan Nama Pemeriksaan</h2>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300 rounded">Standar Kemenkes &le; 60m</span>
                    </div>
                    <input type="text" id="filter-cito-input" placeholder="Cari nama pemeriksaan CITO..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-blue-600 w-full sm:w-64">
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border border-slate-200 text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200 text-center">
                            <tr>
                                <th class="p-2.5 border border-slate-200 w-12" rowspan="2">No</th>
                                <th class="p-2.5 border border-slate-200 w-24 text-left" rowspan="2">Kode</th>
                                <th class="p-2.5 border border-slate-200 text-left" rowspan="2">Nama Test</th>
                                <th class="p-2 border border-slate-200 text-center" colspan="2">Rawat Jalan</th>
                                <th class="p-2 border border-slate-200 text-center" colspan="2">Rawat Inap</th>
                                <th class="p-2 border border-slate-200 text-center bg-slate-200" colspan="2">Total Keseluruhan</th>
                                <th class="p-2.5 border border-slate-200 w-28 text-center" rowspan="2">Kepatuhan (&le; 60m)</th>
                            </tr>
                            <tr>
                                <th class="p-2 border border-slate-200 w-24 text-center">TAT</th>
                                <th class="p-2 border border-slate-200 w-20 text-center">Total</th>
                                <th class="p-2 border border-slate-200 w-24 text-center">TAT</th>
                                <th class="p-2 border border-slate-200 w-20 text-center">Total</th>
                                <th class="p-2 border border-slate-200 w-28 text-center bg-slate-200">Rata-rata TAT</th>
                                <th class="p-2 border border-slate-200 w-24 text-center bg-slate-200">Total Uji</th>
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
                    <div class="flex items-center gap-2">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">TAT NON-CITO - Berdasarkan Nama Pemeriksaan</h2>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300 rounded">Standar Kemenkes &le; 120m</span>
                    </div>
                    <input type="text" id="filter-noncito-input" placeholder="Cari nama pemeriksaan Rutin..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-blue-600 w-full sm:w-64">
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border border-slate-200 text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200 text-center">
                            <tr>
                                <th class="p-2.5 border border-slate-200 w-12" rowspan="2">No</th>
                                <th class="p-2.5 border border-slate-200 w-24 text-left" rowspan="2">Kode</th>
                                <th class="p-2.5 border border-slate-200 text-left" rowspan="2">Nama Test</th>
                                <th class="p-2 border border-slate-200 text-center" colspan="2">Rawat Jalan</th>
                                <th class="p-2 border border-slate-200 text-center" colspan="2">Rawat Inap</th>
                                <th class="p-2 border border-slate-200 text-center bg-slate-200" colspan="2">Total Keseluruhan</th>
                                <th class="p-2.5 border border-slate-200 w-28 text-center" rowspan="2">Kepatuhan (&le; 120m)</th>
                            </tr>
                            <tr>
                                <th class="p-2 border border-slate-200 w-24 text-center">TAT</th>
                                <th class="p-2 border border-slate-200 w-20 text-center">Total</th>
                                <th class="p-2 border border-slate-200 w-24 text-center">TAT</th>
                                <th class="p-2 border border-slate-200 w-20 text-center">Total</th>
                                <th class="p-2 border border-slate-200 w-28 text-center bg-slate-200">Rata-rata TAT</th>
                                <th class="p-2 border border-slate-200 w-24 text-center bg-slate-200">Total Uji</th>
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
                        <td class="p-2 border border-slate-200"><div class="h-3 w-40 bg-slate-200 rounded"></div></td>
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-12 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-8 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 border border-slate-200 text-center"><div class="h-3 w-12 bg-slate-200 rounded mx-auto"></div></td>
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

        function renderRows(items, targetBodyId, targetFootId, searchClass) {
            const tbody = $(targetBodyId);
            const tfoot = $(targetFootId);
            tbody.empty();
            tfoot.empty();

            if (!items || items.length === 0) {
                tbody.html('<tr><td colspan="10" class="p-6 text-center text-slate-400">Tidak ada data pemeriksaan pada periode ini.</td></tr>');
                return;
            }

            let sumRajal = 0, sumRanap = 0, sumTotal = 0, sumOnTime = 0;
            let tbHtml = '';

            items.forEach((item, idx) => {
                sumRajal += item.rajal_count;
                sumRanap += item.ranap_count;
                sumTotal += item.total_count;
                sumOnTime += (item.on_time_count || 0);

                const slaClass = item.sla_percent >= 90 ? 'text-emerald-700 bg-emerald-50' : (item.sla_percent >= 75 ? 'text-amber-700 bg-amber-50' : 'text-rose-700 bg-rose-50');

                tbHtml += `
                    <tr class="hover:bg-slate-50 ${searchClass}">
                        <td class="p-2 border border-slate-200 text-center text-slate-400">${idx + 1}</td>
                        <td class="p-2 border border-slate-200 font-mono font-medium text-slate-600 text-left">${item.code}</td>
                        <td class="p-2 border border-slate-200 font-semibold text-slate-900 text-left target-search-text">${item.name}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-semibold text-blue-700">${item.rajal_tat_formatted}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono">${item.rajal_count > 0 ? item.rajal_count.toLocaleString() : '-'}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-semibold text-emerald-700">${item.ranap_tat_formatted}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono">${item.ranap_count > 0 ? item.ranap_count.toLocaleString() : '-'}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-bold bg-slate-50 text-slate-900">${item.overall_tat_formatted}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-bold bg-slate-50">${item.total_count.toLocaleString()}</td>
                        <td class="p-2 border border-slate-200 text-center font-mono font-bold"><span class="px-1.5 py-0.5 rounded text-[11px] ${slaClass}">${item.sla_percent}%</span></td>
                    </tr>
                `;
            });
            tbody.html(tbHtml);

            // Render Total Footer
            const footSlaPct = sumTotal > 0 ? Math.round((sumOnTime / sumTotal) * 100) : 0;
            const footSlaClass = footSlaPct >= 90 ? 'text-emerald-700' : (footSlaPct >= 75 ? 'text-amber-700' : 'text-rose-700');

            const footHtml = `
                <tr>
                    <td colspan="3" class="p-2 text-left font-bold">TOTAL KESELURUHAN</td>
                    <td class="p-2 text-center text-slate-500">-</td>
                    <td class="p-2 text-center font-mono font-bold">${sumRajal.toLocaleString()}</td>
                    <td class="p-2 text-center text-slate-500">-</td>
                    <td class="p-2 text-center font-mono font-bold">${sumRanap.toLocaleString()}</td>
                    <td class="p-2 text-center text-slate-500 bg-slate-200">-</td>
                    <td class="p-2 text-center font-mono font-black bg-slate-200 text-slate-900">${sumTotal.toLocaleString()}</td>
                    <td class="p-2 text-center font-mono font-black ${footSlaClass}">${footSlaPct}%</td>
                </tr>
            `;
            tfoot.html(footHtml);
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
                url: "{{ route('laporan.tat.data', [], false) }}",
                type: "GET",
                data: { 
                    start_date: startDate, 
                    end_date: endDate,
                    refresh: forceRefresh ? 1 : 0
                },
                success: function(res) {
                    const sum = res.summary || {};
                    $('#kpiTotalCito').text(Number(sum.cito_total || 0).toLocaleString() + ' Uji');
                    $('#kpiAvgCito').text(sum.cito_avg_tat || '-');
                    $('#kpiSlaCito').text((sum.cito_sla_percent || 0) + '%');

                    $('#kpiTotalNonCito').text(Number(sum.non_cito_total || 0).toLocaleString() + ' Uji');
                    $('#kpiAvgNonCito').text(sum.non_cito_avg_tat || '-');
                    $('#kpiSlaNonCito').text((sum.non_cito_sla_percent || 0) + '%');

                    $('#cache-time').text(res.cached_at || 'Baru saja');

                    renderRows(res.cito || [], '#body-cito', '#foot-cito', 'row-cito');
                    renderRows(res.non_cito || [], '#body-noncito', '#foot-noncito', 'row-noncito');
                },
                error: function(xhr, status, err) {
                    console.error("Laporan error:", err);
                    $('#body-cito, #body-noncito').html('<tr><td colspan="10" class="p-6 text-center text-rose-500 font-semibold">Gagal memuat data laporan TAT.</td></tr>');
                },
                complete: function() {
                    btnText.text('Tampilkan');
                    btn.prop('disabled', false).removeClass('opacity-60');
                }
            });
        }

        // Live Search CITO
        $('#filter-cito-input').on('keyup', function() {
            const query = $(this).val().toLowerCase();
            $('.row-cito').each(function() {
                const text = $(this).find('.target-search-text').text().toLowerCase();
                if (text.includes(query) || query === '') $(this).show();
                else $(this).hide();
            });
        });

        // Live Search Non-CITO
        $('#filter-noncito-input').on('keyup', function() {
            const query = $(this).val().toLowerCase();
            $('.row-noncito').each(function() {
                const text = $(this).find('.target-search-text').text().toLowerCase();
                if (text.includes(query) || query === '') $(this).show();
                else $(this).hide();
            });
        });

        // Initial Load
        fetchReportData(false);

        $('#search-button').on('click', () => fetchReportData(false));
        $('#refresh-button').on('click', () => fetchReportData(true));

        $('#export-excel-button').on('click', function() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            window.location.href = `{{ route('laporan.tat.export-excel', [], false) }}?start_date=${start}&end_date=${end}`;
        });
    });
</script>
