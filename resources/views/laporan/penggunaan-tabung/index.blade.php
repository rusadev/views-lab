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

            <!-- Total Usage Metric Banner -->
            <div class="bg-white border border-slate-200 rounded p-4 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Konsumsi Tabung Spesimen</span>
                    <p class="text-xs text-slate-400">Akumulasi tabung yang digunakan pada periode ini.</p>
                </div>
                <div class="text-3xl font-black text-slate-900 font-mono" id="kpiTotalTabung">-</div>
            </div>

            <!-- Table of Tube Usage -->
            <div class="bg-white border border-slate-200 rounded p-4">
                <div class="border-b border-slate-200 pb-2 mb-3">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Tabel Matriks Konsumsi Tabung per Hari</h2>
                    <p class="text-[11px] text-slate-500">Rincian jenis tabung terpakai harian untuk audit persediaan logistik.</p>
                </div>
                <div class="overflow-x-auto">
                    <table id="tabungTable" class="w-full border border-slate-200 text-xs text-center border-collapse">
                        <thead id="tableHeadTabung" class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200"></thead>
                        <tbody id="tableBodyTabung" class="divide-y divide-slate-200 text-slate-800">
                            <!-- Skeleton Rows -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Chart of Tube Usage -->
            <div class="bg-white border border-slate-200 rounded p-4">
                <div class="border-b border-slate-200 pb-2 mb-3">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Grafik Distribusi Pemakaian Tabung</h3>
                    <p class="text-[11px] text-slate-500">Proporsi jenis sampel spesimen yang masuk ke laboratorium.</p>
                </div>
                <div class="w-full min-h-[260px]">
                    <canvas id="tabungChart" style="width: 100%; height: 260px;"></canvas>
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

        let tabungChart = null;

        function initChart() {
            tabungChart = new Chart(document.getElementById('tabungChart'), {
                type: 'bar',
                plugins: [ChartDataLabels],
                data: {
                    labels: [],
                    datasets: []
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

        initChart();

        function showSkeletons() {
            $('#kpiTotalTabung').html('<span class="inline-block h-8 w-32 bg-slate-200 animate-pulse rounded"></span>');
            
            let skelHtml = '';
            for (let i = 0; i < 5; i++) {
                skelHtml += `
                    <tr class="animate-pulse">
                        <td class="p-2"><div class="h-3 w-20 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2"><div class="h-3 w-12 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2"><div class="h-3 w-12 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 bg-slate-50"><div class="h-3 w-16 bg-slate-200 rounded mx-auto"></div></td>
                    </tr>
                `;
            }
            $('#tableHeadTabung').html('<tr><th class="p-2">Tanggal</th><th class="p-2">Serum</th><th class="p-2">EDTA</th><th class="p-2 bg-slate-200">Total</th></tr>');
            $('#tableBodyTabung').html(skelHtml);
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
                data: { 
                    start_date: startDate, 
                    end_date: endDate,
                    refresh: forceRefresh ? 1 : 0
                },
                success: function(res) {
                    $('#cache-time').text(res.cached_at || 'Baru saja');
                    const samples = res.samples || [];
                    const data = res.data || [];
                    const grandTotal = res.total_keseluruhan || 0;

                    $('#kpiTotalTabung').text(grandTotal.toLocaleString() + ' Tabung');

                    // 1. Table Header
                    let thHtml = `<tr><th class="p-2 text-left">Tanggal</th>`;
                    samples.forEach(s => thHtml += `<th class="p-2">${s}</th>`);
                    thHtml += `<th class="p-2 bg-slate-200">Total</th></tr>`;
                    $('#tableHeadTabung').html(thHtml);

                    // 2. Table Body
                    let tbHtml = '';
                    const sampleSums = {};
                    samples.forEach(s => sampleSums[s] = 0);
                    let sumTotal = 0;

                    data.forEach(row => {
                        tbHtml += `<tr class="hover:bg-slate-50">`;
                        tbHtml += `<td class="p-2 text-left font-semibold text-slate-800 font-mono">${row.tanggal}</td>`;
                        samples.forEach(s => {
                            const val = row[s] || 0;
                            sampleSums[s] += val;
                            tbHtml += `<td class="p-2 font-mono">${val.toLocaleString()}</td>`;
                        });
                        tbHtml += `<td class="p-2 font-bold bg-slate-50 font-mono">${row.total.toLocaleString()}</td>`;
                        tbHtml += `</tr>`;
                        sumTotal += row.total;
                    });

                    // Summary Row
                    if (data.length > 0) {
                        tbHtml += `<tr class="bg-slate-100 font-black border-t-2 border-slate-300">`;
                        tbHtml += `<td class="p-2 text-left">TOTAL</td>`;
                        samples.forEach(s => {
                            tbHtml += `<td class="p-2 font-mono">${sampleSums[s].toLocaleString()}</td>`;
                        });
                        tbHtml += `<td class="p-2 bg-slate-200 font-mono">${sumTotal.toLocaleString()}</td>`;
                        tbHtml += `</tr>`;
                    } else {
                        tbHtml = '<tr><td colspan="15" class="p-4 text-center text-slate-400">Tidak ada data penggunaan tabung pada periode ini.</td></tr>';
                    }

                    $('#tableBodyTabung').html(tbHtml);

                    // 3. Update Chart
                    const dates = data.map(d => d.tanggal);
                    const colors = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#db2777', '#0891b2', '#4b5563'];

                    const datasets = samples.map((sample, idx) => ({
                        label: sample,
                        data: data.map(d => d[sample] || 0),
                        backgroundColor: colors[idx % colors.length],
                        borderRadius: 3
                    }));

                    tabungChart.data.labels = dates;
                    tabungChart.data.datasets = datasets;
                    tabungChart.update();
                },
                error: function(xhr, status, err) {
                    console.error("Laporan error:", err);
                },
                complete: function() {
                    btnText.text('Tampilkan');
                    btn.prop('disabled', false).removeClass('opacity-60');
                }
            });
        }

        fetchReportData(false);

        $('#search-button').on('click', () => fetchReportData(false));
        $('#refresh-button').on('click', () => fetchReportData(true));

        $('#export-excel-button').on('click', function() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            window.location.href = `{{ route('laporan.penggunaan-tabung.export-excel', [], false) }}?start_date=${start}&end_date=${end}`;
        });
    });
</script>