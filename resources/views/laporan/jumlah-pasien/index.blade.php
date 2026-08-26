@section('title', 'Laporan Jumlah Pasien')

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
                            <h1 class="text-base font-bold text-slate-900">Laporan Jumlah Pasien</h1>
                            <p class="text-xs text-slate-500 mt-0.5">Rekapitulasi kunjungan pasien per jenis rawat, kelompok umur, gender, dan unit ruangan.</p>
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

                        <button id="export-word-button" type="button" class="h-9 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded border border-slate-300 transition-colors flex items-center gap-1.5 whitespace-nowrap">
                            <span>Word (.docx)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table 1: Rekapitulasi per Jenis Pelayanan -->
            <div class="bg-white border border-slate-200 rounded p-4">
                <div class="border-b border-slate-200 pb-2 mb-3">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rekapitulasi Kunjungan Pasien per Jenis Pelayanan</h2>
                    <p class="text-[11px] text-slate-500">Jumlah pasien unik berdasarkan Rawat Jalan, Rawat Inap, dan Layanan Lainnya.</p>
                </div>
                <div class="overflow-x-auto">
                    <table id="laporanTable" class="w-full border border-slate-200 text-xs text-center border-collapse">
                        <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200">
                            <tr id="tableHeader"></tr>
                        </thead>
                        <tbody id="tableBody" class="divide-y divide-slate-200 text-slate-800">
                            <!-- Skeleton Rows -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Visual Charts (3 cols) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-stretch">
                <!-- Chart 1: Distribusi Layanan -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="border-b border-slate-200 pb-2 mb-2">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Distribusi Tipe Layanan</h3>
                        <p class="text-[10px] text-slate-500">Proporsi Rawat Inap vs Rawat Jalan.</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center min-h-[180px]">
                        <canvas id="totalPieChart" style="max-height: 180px;"></canvas>
                    </div>
                </div>

                <!-- Chart 2: Distribusi Gender -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="border-b border-slate-200 pb-2 mb-2">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Distribusi Gender</h3>
                        <p class="text-[10px] text-slate-500">Perbandingan Laki-laki vs Perempuan.</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center min-h-[180px]">
                        <canvas id="genderPieChart" style="max-height: 180px;"></canvas>
                    </div>
                </div>

                <!-- Chart 3: Kelompok Usia -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="border-b border-slate-200 pb-2 mb-2">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Distribusi Kelompok Usia</h3>
                        <p class="text-[10px] text-slate-500">Rentang umur pasien terlayani.</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center min-h-[180px]">
                        <canvas id="ageBarChart" style="width: 100%; height: 180px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Table 2: Rekapitulasi per Ruangan -->
            <div class="bg-white border border-slate-200 rounded p-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-200 pb-3 mb-3">
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rekapitulasi Kunjungan Pasien per Ruangan / Poliklinik</h2>
                        <p class="text-[11px] text-slate-500">Rincian pasien terdistribusi di setiap unit bangsal rawat inap dan poliklinik rawat jalan.</p>
                    </div>
                    <input type="text" id="filter-ruangan-input" placeholder="Cari nama ruangan..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-blue-600 w-full sm:w-60">
                </div>
                <div class="overflow-x-auto">
                    <table id="laporanTableRuangan" class="w-full border border-slate-200 text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200">
                            <tr id="tableHeaderRuangan"></tr>
                        </thead>
                        <tbody id="tableBodyRuangan" class="divide-y divide-slate-200 text-slate-800">
                            <!-- Skeleton Rows -->
                        </tbody>
                    </table>
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

        let totalPieChart = null;
        let genderPieChart = null;
        let ageBarChart = null;

        function initCharts() {
            totalPieChart = new Chart(document.getElementById('totalPieChart'), {
                type: 'doughnut',
                plugins: [ChartDataLabels],
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#2563eb', '#059669', '#d97706'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { boxWidth: 10, font: { family: 'Plus Jakarta Sans', size: 10 } } },
                        datalabels: {
                            display: (ctx) => ctx.dataset.data[ctx.dataIndex] > 0,
                            color: '#ffffff',
                            font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 11 },
                            formatter: (v) => v
                        }
                    }
                }
            });

            genderPieChart = new Chart(document.getElementById('genderPieChart'), {
                type: 'doughnut',
                plugins: [ChartDataLabels],
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#2563eb', '#ec4899', '#94a3b8'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { boxWidth: 10, font: { family: 'Plus Jakarta Sans', size: 10 } } },
                        datalabels: {
                            display: (ctx) => ctx.dataset.data[ctx.dataIndex] > 0,
                            color: '#ffffff',
                            font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 11 },
                            formatter: (v) => v
                        }
                    }
                }
            });

            ageBarChart = new Chart(document.getElementById('ageBarChart'), {
                type: 'bar',
                plugins: [ChartDataLabels],
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Pasien',
                        data: [],
                        backgroundColor: '#2563eb',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 20 } },
                    plugins: {
                        legend: { display: false },
                        datalabels: {
                            display: true,
                            color: '#0f172a',
                            anchor: 'end',
                            align: 'top',
                            offset: 2,
                            font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 11 },
                            formatter: (v) => v > 0 ? v : ''
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, grace: '15%', grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } } },
                        x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' } } }
                    }
                }
            });
        }

        initCharts();

        function showSkeletons() {
            let skel1 = '';
            for (let i = 0; i < 4; i++) {
                skel1 += `
                    <tr class="animate-pulse">
                        <td class="p-2 text-left"><div class="h-3 w-28 bg-slate-200 rounded"></div></td>
                        <td class="p-2"><div class="h-3 w-12 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 bg-slate-50"><div class="h-3 w-16 bg-slate-200 rounded mx-auto"></div></td>
                    </tr>
                `;
            }
            $('#tableHeader').html('<th class="p-2 text-left">Jenis Pelayanan</th><th class="p-2">Bulan</th><th class="p-2 bg-slate-200">Total</th>');
            $('#tableBody').html(skel1);

            let skel2 = '';
            for (let i = 0; i < 6; i++) {
                skel2 += `
                    <tr class="animate-pulse">
                        <td class="p-2 text-left"><div class="h-3 w-20 bg-slate-200 rounded"></div></td>
                        <td class="p-2 text-left"><div class="h-3 w-40 bg-slate-200 rounded"></div></td>
                        <td class="p-2"><div class="h-3 w-12 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="p-2 bg-slate-50"><div class="h-3 w-16 bg-slate-200 rounded mx-auto"></div></td>
                    </tr>
                `;
            }
            $('#tableHeaderRuangan').html('<th class="p-2 text-left">Tipe</th><th class="p-2 text-left">Nama Ruangan</th><th class="p-2 text-center">Bulan</th><th class="p-2 bg-slate-200 text-center">Total</th>');
            $('#tableBodyRuangan').html(skel2);
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
                url: "{{ route('laporan.jumlah-pasien.data') }}",
                type: "GET",
                data: { 
                    start_date: startDate, 
                    end_date: endDate,
                    refresh: forceRefresh ? 1 : 0
                },
                success: function(res) {
                    $('#cache-time').text(res.cached_at || 'Baru saja');

                    // 1. Render Table 1: Tipe Ruangan
                    const dist = res.distribusi_ruangan || {};
                    const tipeRuangan = dist['Tipe Ruangan'] || {};

                    const monthSet = {};
                    Object.keys(tipeRuangan).forEach(k => {
                        if (k !== 'Total Per Bulan' && typeof tipeRuangan[k] === 'object') {
                            Object.keys(tipeRuangan[k]).forEach(m => {
                                if (m !== 'Total') monthSet[m] = true;
                            });
                        }
                    });
                    const months = Object.keys(monthSet).sort();

                    let thHtml = '<th class="p-2 text-left">Jenis Pelayanan</th>';
                    months.forEach(m => thHtml += `<th class="p-2">${m}</th>`);
                    thHtml += '<th class="p-2 bg-slate-200">Total</th>';
                    $('#tableHeader').html(thHtml);

                    let tbHtml = '';
                    Object.keys(tipeRuangan).forEach(k => {
                        if (k === 'Total Per Bulan') return;
                        tbHtml += `<tr class="hover:bg-slate-50">`;
                        tbHtml += `<td class="p-2 text-left font-bold">${k}</td>`;
                        months.forEach(m => {
                            tbHtml += `<td class="p-2">${tipeRuangan[k][m] || 0}</td>`;
                        });
                        tbHtml += `<td class="p-2 font-bold bg-slate-50">${tipeRuangan[k]['Total'] || 0}</td>`;
                        tbHtml += `</tr>`;
                    });

                    if (tipeRuangan['Total Per Bulan']) {
                        tbHtml += `<tr class="bg-slate-100 font-black border-t-2 border-slate-300">`;
                        tbHtml += `<td class="p-2 text-left">TOTAL</td>`;
                        months.forEach(m => {
                            tbHtml += `<td class="p-2">${tipeRuangan['Total Per Bulan'][m] || 0}</td>`;
                        });
                        tbHtml += `<td class="p-2 bg-slate-200">${tipeRuangan['Total Per Bulan']['Total'] || 0}</td>`;
                        tbHtml += `</tr>`;
                    }
                    $('#tableBody').html(tbHtml || '<tr><td colspan="10" class="p-4 text-center text-slate-400">Tidak ada data ditemukan.</td></tr>');

                    // 2. Render Charts
                    const labelsTipe = Object.keys(tipeRuangan).filter(k => k !== 'Total Per Bulan');
                    const valuesTipe = labelsTipe.map(k => tipeRuangan[k]['Total'] || 0);
                    totalPieChart.data.labels = labelsTipe;
                    totalPieChart.data.datasets[0].data = valuesTipe;
                    totalPieChart.update();

                    const gender = dist['Gender'] || {};
                    genderPieChart.data.labels = Object.keys(gender);
                    genderPieChart.data.datasets[0].data = Object.values(gender);
                    genderPieChart.update();

                    const usia = dist['Usia'] || {};
                    ageBarChart.data.labels = Object.keys(usia);
                    ageBarChart.data.datasets[0].data = Object.values(usia);
                    ageBarChart.update();

                    // 3. Render Table 2: Per Ruangan
                    const distRuang = res.getDistribusiPerRuangan || {};
                    const ruangData = distRuang.data || {};
                    const monthsRuang = (distRuang.months || []).sort();

                    let thRuang = '<th class="p-2 text-left">Tipe</th><th class="p-2 text-left">Nama Ruangan / Poliklinik</th>';
                    monthsRuang.forEach(m => thRuang += `<th class="p-2 text-center">${m}</th>`);
                    thRuang += '<th class="p-2 text-center bg-slate-200">Total</th>';
                    $('#tableHeaderRuangan').html(thRuang);

                    let tbRuang = '';
                    Object.keys(ruangData).forEach(tipe => {
                        const units = Object.keys(ruangData[tipe]).filter(k => k !== 'Total').sort();
                        units.forEach(u => {
                            tbRuang += `<tr class="hover:bg-slate-50 row-ruang">`;
                            tbRuang += `<td class="p-2 text-left text-slate-500 font-medium">${tipe}</td>`;
                            tbRuang += `<td class="p-2 text-left font-semibold text-slate-800 search-ruang-target">${u}</td>`;
                            monthsRuang.forEach(m => {
                                tbRuang += `<td class="p-2 text-center">${ruangData[tipe][u][m] || 0}</td>`;
                            });
                            tbRuang += `<td class="p-2 text-center font-bold bg-slate-50">${ruangData[tipe][u]['Total'] || 0}</td>`;
                            tbRuang += `</tr>`;
                        });

                        if (ruangData[tipe]['Total']) {
                            tbRuang += `<tr class="bg-slate-100 font-bold border-t border-slate-300">`;
                            tbRuang += `<td colspan="2" class="p-2 text-left">Subtotal ${tipe}</td>`;
                            monthsRuang.forEach(m => {
                                tbRuang += `<td class="p-2 text-center">${ruangData[tipe]['Total'][m] || 0}</td>`;
                            });
                            tbRuang += `<td class="p-2 text-center bg-slate-200">${ruangData[tipe]['Total']['Total'] || 0}</td>`;
                            tbRuang += `</tr>`;
                        }
                    });
                    $('#tableBodyRuangan').html(tbRuang || '<tr><td colspan="10" class="p-4 text-center text-slate-400">Tidak ada data ditemukan.</td></tr>');
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

        // Live Room Search
        $('#filter-ruangan-input').on('keyup', function() {
            const query = $(this).val().toLowerCase();
            $('.row-ruang').each(function() {
                const text = $(this).find('.search-ruang-target').text().toLowerCase();
                if (text.includes(query) || query === '') $(this).show();
                else $(this).hide();
            });
        });

        fetchReportData(false);

        $('#search-button').on('click', () => fetchReportData(false));
        $('#refresh-button').on('click', () => fetchReportData(true));

        $('#export-excel-button').on('click', function() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            window.location.href = `{{ route('laporan.jumlah-pasien.export-excel') }}?start_date=${start}&end_date=${end}`;
        });

        $('#export-word-button').on('click', function() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            window.location.href = `{{ route('laporan.jumlah-pasien.export-word') }}?start_date=${start}&end_date=${end}`;
        });
    });
</script>