@section('title', 'Dashboard Laboratorium Patologi Klinik')

<x-app-layout>
    <div class="py-4 bg-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            
            <!-- Clean Unified Header & Filter Card (Flat) -->
            <div class="bg-white border border-slate-200 rounded p-4 flex flex-col xl:flex-row justify-between items-start xl:items-center gap-3">
                <div>
                    <h1 class="text-base font-bold text-slate-900">Dashboard Laboratorium Patologi Klinik</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Monitoring data kunjungan, rata-rata TAT, beban spesimen, dan nilai kritis.</p>
                </div>

                <!-- Unified Filter Bar -->
                <div class="flex flex-wrap items-center gap-2 text-xs w-full xl:w-auto">
                    <!-- Quick Presets -->
                    <div class="inline-flex border border-slate-300 rounded overflow-hidden bg-slate-100">
                        <button type="button" class="dash-preset px-2.5 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300" data-days="0">Hari Ini</button>
                        <button type="button" class="dash-preset px-2.5 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300" data-days="7">7 Hari</button>
                        <button type="button" class="dash-preset px-2.5 py-1.5 text-slate-700 hover:bg-white text-xs font-semibold transition-colors border-r border-slate-300" data-days="30">30 Hari</button>
                    </div>

                    <!-- Date Inputs -->
                    <div class="flex items-center gap-1 bg-slate-50 p-1 border border-slate-300 rounded">
                        <input type="date" id="startDate" class="h-7 px-2 bg-white border border-slate-300 rounded text-xs text-slate-800 outline-none focus:border-blue-600 font-mono">
                        <span class="text-slate-400 text-xs">s/d</span>
                        <input type="date" id="endDate" class="h-7 px-2 bg-white border border-slate-300 rounded text-xs text-slate-800 outline-none focus:border-blue-600 font-mono">
                    </div>

                    <!-- Action Buttons -->
                    <button id="fetchDataButton" 
                            class="h-9 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded border border-blue-700 transition-colors whitespace-nowrap">
                        <span id="btnText">Tampilkan</span>
                    </button>

                    <button id="refreshCacheButton" title="Perbarui data"
                            class="h-9 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded border border-slate-300 transition-colors whitespace-nowrap">
                        Refresh
                    </button>
                </div>
            </div>

            <!-- 4 Top KPI Cards (Clean & Rich Information) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                
                <!-- KPI 1: Pasien Terlayani -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Kunjungan Pasien</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 rounded">Pasien Unik</span>
                    </div>
                    <div class="my-3">
                        <div class="text-3xl font-black text-slate-900 font-mono tracking-tight" id="kunjunganPasien">-</div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2 flex">
                            <div id="barRajal" class="bg-blue-600 h-full" style="width: 60%"></div>
                            <div id="barRanap" class="bg-emerald-500 h-full" style="width: 40%"></div>
                        </div>
                    </div>
                    <div class="text-[11px] text-slate-600 flex justify-between font-medium pt-1 border-t border-slate-100">
                        <span id="kpiRajalSub">Rawat Jalan: -</span>
                        <span id="kpiRanapSub">Rawat Inap: -</span>
                    </div>
                </div>

                <!-- KPI 2: Total Permintaan Pemeriksaan -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Total Permintaan</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 rounded">Order</span>
                    </div>
                    <div class="my-3">
                        <div class="text-3xl font-black text-slate-900 font-mono tracking-tight" id="permintaanPemeriksaan">-</div>
                        <div class="text-[11px] text-slate-500 mt-2 font-medium">
                            Rasio: <strong id="kpiRasioItem" class="text-slate-800">-</strong> Parameter / Pasien
                        </div>
                    </div>
                    <div class="text-[11px] text-slate-600 flex justify-between font-medium pt-1 border-t border-slate-100">
                        <span>Total Parameter: <strong id="kpiTotalParameter" class="text-slate-800 font-mono">-</strong></span>
                        <span class="text-slate-400">Pemeriksaan</span>
                    </div>
                </div>

                <!-- KPI 3: Pemeriksaan Selesai -->
                <div class="bg-white border border-emerald-300 rounded p-4 flex flex-col justify-between bg-emerald-50/20">
                    <div class="flex items-center justify-between border-b border-emerald-200 pb-2">
                        <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Pemeriksaan Selesai</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 rounded" id="kpiSelesaiPercent">0%</span>
                    </div>
                    <div class="my-3">
                        <div class="text-3xl font-black text-emerald-700 font-mono tracking-tight" id="pemeriksaanSelesai">-</div>
                        <div class="w-full bg-emerald-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div id="barSelesaiProgress" class="bg-emerald-600 h-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="text-[11px] text-emerald-800 flex justify-between font-medium pt-1 border-t border-emerald-100">
                        <span>Status: <strong>Tervalidasi</strong></span>
                        <span id="kpiSelesaiLabel">0 Order</span>
                    </div>
                </div>

                <!-- KPI 4: Menunggu / Sedang Dikerjakan -->
                <div class="bg-white border border-amber-300 rounded p-4 flex flex-col justify-between bg-amber-50/20">
                    <div class="flex items-center justify-between border-b border-amber-200 pb-2">
                        <span class="text-xs font-bold text-amber-900 uppercase tracking-wider">Menunggu / Diproses</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 rounded">In-Progress</span>
                    </div>
                    <div class="my-3">
                        <div class="text-3xl font-black text-amber-700 font-mono tracking-tight" id="pemeriksaanBelumDikerjakan">-</div>
                        <div class="w-full bg-amber-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div id="barProsesProgress" class="bg-amber-500 h-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="text-[11px] text-amber-800 flex justify-between font-medium pt-1 border-t border-amber-100">
                        <span>Dalam Pengujian</span>
                        <span id="kpiProsesPercent">0% Total</span>
                    </div>
                </div>

            </div>

            <!-- Turn Around Time (TAT) & Critical Values Row (2 Balanced Columns) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
                
                <!-- Left: TAT Performance Chart (7 cols) -->
                <div class="lg:col-span-7 bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="border-b border-slate-200 pb-2 mb-3">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rata-Rata Turn Around Time (TAT)</h2>
                        <p class="text-[11px] text-slate-500">Durasi dari penerimaan spesimen hingga validasi hasil (dalam menit).</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center flex-1">
                        <div class="md:col-span-8 min-h-[230px]">
                            <canvas id="averageTATChart" style="width: 100%; height: 230px;"></canvas>
                        </div>
                        <div class="md:col-span-4 bg-slate-50 border border-slate-200 rounded p-3 space-y-3 text-xs h-full flex flex-col justify-center">
                            <div>
                                <div class="text-[11px] text-slate-500 font-medium">Rata-Rata Seluruh Uji:</div>
                                <div class="text-2xl font-black text-slate-900 font-mono" id="tatOverallAvg">-</div>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500 font-medium">Pemeriksaan Tercepat:</div>
                                <div class="font-bold text-emerald-800 text-xs" id="tatFastest">-</div>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-500 font-medium">Beban Terbanyak:</div>
                                <div class="font-bold text-blue-900 text-xs" id="tatHeaviest">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Monitoring Nilai Kritis Stream (5 cols) - STRICT CLEAN SCROLL -->
                <div class="lg:col-span-5 bg-white border-2 border-rose-400 rounded flex flex-col overflow-hidden justify-between">
                    <!-- Header -->
                    <div class="bg-rose-600 px-4 py-3 text-white flex items-center justify-between shrink-0">
                        <div>
                            <h2 class="text-xs font-black uppercase tracking-wider text-white">NILAI KRITIS REALTIME</h2>
                            <p class="text-[11px] text-rose-100">Batas Kritis (HH / LL)</p>
                        </div>
                        <span id="criticalBadgeCount" class="px-2 py-0.5 text-xs font-black rounded bg-white text-rose-700 border border-rose-200">
                            0 Kasus
                        </span>
                    </div>

                    <!-- Fixed Table Subheader -->
                    <div class="bg-rose-100 border-b border-rose-200 text-rose-950 font-bold text-[10px] uppercase px-3 py-2 grid grid-cols-12 gap-1 items-center shrink-0">
                        <div class="col-span-4">Pasien / RM</div>
                        <div class="col-span-3">Uji Lab / Ruang</div>
                        <div class="col-span-2 text-right">Hasil</div>
                        <div class="col-span-1 text-center">Flag</div>
                        <div class="col-span-2 text-right">Aksi</div>
                    </div>

                    <!-- Scrollable Body with Clean Containment -->
                    <div id="nilai-kritis-container" class="overflow-y-auto flex-1 bg-white divide-y divide-rose-100" style="max-height: 220px; min-height: 180px;">
                        <div class="p-6 text-center text-slate-400 text-xs">Memuat data nilai kritis...</div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-rose-50 px-3 py-2 border-t border-rose-200 text-[11px] text-rose-800 flex justify-between items-center shrink-0">
                        <span>Hasil melewati batas kritis.</span>
                        <a href="{{ route('klinik.index') }}" class="font-bold text-rose-900 hover:underline">Buka Patologi Klinik &rarr;</a>
                    </div>
                </div>

            </div>

            <!-- Distribution Analytics Row (4 Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">
                
                <!-- Donut: Status Rawat Jalan -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="border-b border-slate-200 pb-2 mb-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rawat Jalan</h3>
                            <span class="px-2 py-0.2 text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 rounded">Poliklinik</span>
                        </div>
                        <p class="text-[10px] text-slate-500">Tingkat penyelesaian hasil rawat jalan.</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center min-h-[160px] relative">
                        <canvas id="donutChartRawatJalan" style="max-height: 160px;"></canvas>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex justify-between text-xs font-semibold">
                        <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200" id="selesaiDataRajal">Selesai: 0</span>
                        <span class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200" id="belumSelesaiDataRajal">Belum: 0</span>
                    </div>
                </div>

                <!-- Donut: Status Rawat Inap -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="border-b border-slate-200 pb-2 mb-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rawat Inap</h3>
                            <span class="px-2 py-0.2 text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 rounded">Bangsal / ICU</span>
                        </div>
                        <p class="text-[10px] text-slate-500">Tingkat penyelesaian hasil rawat inap.</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center min-h-[160px] relative">
                        <canvas id="donutChartRawatInap" style="max-height: 160px;"></canvas>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex justify-between text-xs font-semibold">
                        <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200" id="selesaiDataRanap">Selesai: 0</span>
                        <span class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200" id="belumSelesaiDataRanap">Belum: 0</span>
                    </div>
                </div>

                <!-- Donut: Distribusi Spesimen -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="border-b border-slate-200 pb-2 mb-2">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Distribusi Spesimen</h3>
                        <p class="text-[10px] text-slate-500">Proporsi jenis sampel spesimen.</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center min-h-[160px]">
                        <canvas id="distribusiSpesimenChart" style="max-height: 160px;"></canvas>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-500 text-center font-medium">
                        Darah EDTA, Serum, Urine, & Tabung Khusus
                    </div>
                </div>

                <!-- Donut: Distribusi Kelompok Lab -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="border-b border-slate-200 pb-2 mb-2">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Kelompok Pemeriksaan</h3>
                        <p class="text-[10px] text-slate-500">Proporsi per kelompok laboratorium.</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center min-h-[160px]">
                        <canvas id="distribusiPemeriksaanChart" style="max-height: 160px;"></canvas>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-500 text-center font-medium">
                        Hematologi, Kimia Klinik, Imunologi, Urinalisa
                    </div>
                </div>

            </div>

            <!-- Workload & Peak Hours (2 Balanced Full-Width Cards) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch">
                
                <!-- Top 8 Pemeriksaan -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="border-b border-slate-200 pb-2 mb-3 flex justify-between items-center">
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Top 8 Pemeriksaan Terbanyak</h3>
                            <p class="text-[11px] text-slate-500">Volume dan status penyelesaian parameter uji laboratorium.</p>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300 rounded">Volume</span>
                    </div>
                    <div class="w-full min-h-[220px]">
                        <canvas id="permintaanPemeriksaanChart" style="width: 100%; height: 220px;"></canvas>
                    </div>
                </div>

                <!-- Permintaan Berdasarkan Waktu -->
                <div class="bg-white border border-slate-200 rounded p-4 flex flex-col justify-between">
                    <div class="border-b border-slate-200 pb-2 mb-3 flex justify-between items-center">
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Tren Volume Permintaan Per Jam</h3>
                            <p class="text-[11px] text-slate-500">Distribusi beban kerja berdasarkan waktu kedatangan.</p>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 rounded" id="peakHourBadge">Puncak: 08:00</span>
                    </div>
                    <div class="w-full min-h-[220px]">
                        <canvas id="permintaanPerWaktuChart" style="width: 100%; height: 220px;"></canvas>
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
        // Register DataLabels globally
        if (typeof ChartDataLabels !== 'undefined') {
            Chart.register(ChartDataLabels);
        }

        // Date Presets Helper
        function setDashDateRange(daysAgo) {
            const today = new Date();
            const fromDate = new Date();
            fromDate.setDate(today.getDate() - daysAgo);

            document.getElementById("startDate").value = fromDate.toISOString().split('T')[0];
            document.getElementById("endDate").value = today.toISOString().split('T')[0];
        }

        setDashDateRange(0);

        document.querySelectorAll('.dash-preset').forEach(btn => {
            btn.addEventListener('click', function() {
                const days = parseInt(this.getAttribute('data-days'), 10);
                setDashDateRange(days);
                fetchDashboardData(false);
            });
        });

        // Charts Global Storage
        let chartRawatJalan = null;
        let chartRawatInap = null;
        let chartAverageTAT = null;
        let chartDistribusiSpesimen = null;
        let chartDistribusiPemeriksaan = null;
        let chartPermintaanPemeriksaan = null;
        let chartPermintaanPerWaktu = null;

        // Custom Plugin for Center Text on Donut Charts
        const centerTextPlugin = {
            id: 'centerTextPlugin',
            afterDraw: function(chart) {
                if (chart.config.type !== 'doughnut' || !chart.config.options?.plugins?.centerText?.display) return;
                
                let width = chart.width, height = chart.height, ctx = chart.ctx;
                ctx.restore();
                
                let total = (chart.data.datasets[0].data[0] || 0) + (chart.data.datasets[0].data[1] || 0);
                let completed = chart.data.datasets[0].data[0] || 0;
                let pct = total > 0 ? ((completed / total) * 100).toFixed(1) + '%' : '0%';

                ctx.font = 'bold 15px "Plus Jakarta Sans", sans-serif';
                ctx.textBaseline = "middle";
                ctx.textAlign = "center";
                ctx.fillStyle = "#0f172a";
                ctx.fillText(pct, width / 2, height / 2 - 6);

                ctx.font = '600 10px "Plus Jakarta Sans", sans-serif';
                ctx.fillStyle = "#64748b";
                ctx.fillText("Selesai", width / 2, height / 2 + 10);
                ctx.save();
            }
        };

        // Initialize Charts with DataLabels & Modern Flat Palette
        function initCharts() {
            // Doughnut: Rajal
            chartRawatJalan = new Chart(document.getElementById('donutChartRawatJalan'), {
                type: 'doughnut',
                plugins: [ChartDataLabels, centerTextPlugin],
                data: {
                    labels: ['Selesai', 'Belum Selesai'],
                    datasets: [{
                        data: [0, 0],
                        backgroundColor: ['#059669', '#f43f5e'],
                        borderWidth: 3,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        centerText: { display: true },
                        datalabels: {
                            display: function(ctx) {
                                return ctx.dataset.data[ctx.dataIndex] > 0;
                            },
                            color: '#ffffff',
                            font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 11 },
                            formatter: (v) => v
                        }
                    }
                }
            });

            // Doughnut: Ranap
            chartRawatInap = new Chart(document.getElementById('donutChartRawatInap'), {
                type: 'doughnut',
                plugins: [ChartDataLabels, centerTextPlugin],
                data: {
                    labels: ['Selesai', 'Belum Selesai'],
                    datasets: [{
                        data: [0, 0],
                        backgroundColor: ['#059669', '#f43f5e'],
                        borderWidth: 3,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        centerText: { display: true },
                        datalabels: {
                            display: function(ctx) {
                                return ctx.dataset.data[ctx.dataIndex] > 0;
                            },
                            color: '#ffffff',
                            font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 11 },
                            formatter: (v) => v
                        }
                    }
                }
            });

            // Bar: Average TAT (With 25% Grace room on Y-axis so 180m is never cut off!)
            chartAverageTAT = new Chart(document.getElementById('averageTATChart'), {
                type: 'bar',
                plugins: [ChartDataLabels],
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Rata-rata TAT (Menit)',
                        data: [],
                        backgroundColor: '#2563eb',
                        borderRadius: 4,
                        maxBarThickness: 45
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 25
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        datalabels: {
                            display: true,
                            color: '#0f172a',
                            anchor: 'end',
                            align: 'top',
                            offset: 4,
                            font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 11 },
                            formatter: (v) => v > 0 ? `${v}m` : ''
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grace: '25%', // 25% headroom on top of highest bar!
                            grid: { color: '#f1f5f9' }, 
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } } 
                        },
                        x: { 
                            grid: { display: false }, 
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' } } 
                        }
                    }
                }
            });

            // Doughnut: Spesimen with DataLabels
            chartDistribusiSpesimen = new Chart(document.getElementById('distribusiSpesimenChart'), {
                type: 'doughnut',
                plugins: [ChartDataLabels],
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#2563eb', '#059669', '#d97706', '#7c3aed', '#db2777', '#0891b2'],
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
                            display: function(ctx) {
                                return ctx.dataset.data[ctx.dataIndex] > 0;
                            },
                            color: '#ffffff',
                            font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 10 },
                            formatter: (v) => v
                        }
                    }
                }
            });

            // Doughnut: Kelompok Uji with DataLabels
            chartDistribusiPemeriksaan = new Chart(document.getElementById('distribusiPemeriksaanChart'), {
                type: 'doughnut',
                plugins: [ChartDataLabels],
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4'],
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
                            display: function(ctx) {
                                return ctx.dataset.data[ctx.dataIndex] > 0;
                            },
                            color: '#ffffff',
                            font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 10 },
                            formatter: (v) => v
                        }
                    }
                }
            });

            // Stacked Bar: Top 8 Pemeriksaan with DataLabels
            chartPermintaanPemeriksaan = new Chart(document.getElementById('permintaanPemeriksaanChart'), {
                type: 'bar',
                plugins: [ChartDataLabels],
                data: {
                    labels: [],
                    datasets: [
                        { label: 'Selesai', data: [], backgroundColor: '#059669', borderRadius: 4 },
                        { label: 'Belum Selesai', data: [], backgroundColor: '#f43f5e', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 20
                        }
                    },
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' } } },
                        datalabels: {
                            display: function(ctx) {
                                return ctx.dataset.data[ctx.dataIndex] > 5;
                            },
                            color: '#ffffff',
                            font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 10 },
                            formatter: (v) => v
                        }
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } } },
                        y: { stacked: true, beginAtZero: true, grace: '15%', grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } } }
                    }
                }
            });

            // Multi-line: Permintaan Per Waktu with DataLabels
            chartPermintaanPerWaktu = new Chart(document.getElementById('permintaanPerWaktuChart'), {
                type: 'line',
                plugins: [ChartDataLabels],
                data: {
                    labels: [],
                    datasets: [
                        { label: 'Total Order', data: [], borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.1)', fill: true, tension: 0.3, borderWidth: 2.5, pointRadius: 3 },
                        { label: 'Rawat Jalan', data: [], borderColor: '#059669', borderDash: [4, 4], tension: 0.3, borderWidth: 2, pointRadius: 2 },
                        { label: 'Rawat Inap', data: [], borderColor: '#d97706', borderDash: [2, 2], tension: 0.3, borderWidth: 2, pointRadius: 2 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 20
                        }
                    },
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' } } },
                        datalabels: {
                            display: (context) => context.datasetIndex === 0 && context.dataset.data[context.dataIndex] > 0,
                            color: '#1e40af',
                            anchor: 'end',
                            align: 'top',
                            offset: 3,
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

        initCharts();

        // Main Fetch Function
        function fetchDashboardData(forceRefresh = false) {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const btn = $('#fetchDataButton');
            const btnText = $('#btnText');

            btnText.text('Memuat...');
            btn.prop('disabled', true).addClass('opacity-60');

            $.ajax({
                url: "{{ route('dashboard.data') }}",
                type: "GET",
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    refresh: forceRefresh ? 1 : 0
                },
                success: function(res) {
                    const kunjungan = res.kunjunganData || {};
                    const status = res.statusPemeriksaan || {};
                    const rajalRanap = res.permintaanRawatJalanInap || {};
                    const criticals = res.nilaiKritis || [];

                    const totalPasien = parseInt(kunjungan.kunjungan_pasien || 0);
                    const totalPermintaan = parseInt(kunjungan.jumlah_permintaan || 0);
                    const totalSelesai = parseInt(kunjungan.pemeriksaan_selesai || status.total_selesai || 0);
                    const totalProses = parseInt(kunjungan.pemeriksaan_di_proses || status.total_diproses || 0);
                    const totalParameter = parseInt(status.total_pemeriksaan || 0);

                    // 1. KPI Update
                    $('#kunjunganPasien').text(totalPasien.toLocaleString());
                    $('#permintaanPemeriksaan').text(totalPermintaan.toLocaleString());
                    $('#pemeriksaanSelesai').text(totalSelesai.toLocaleString());
                    $('#pemeriksaanBelumDikerjakan').text(totalProses.toLocaleString());
                    $('#kpiTotalParameter').text(totalParameter.toLocaleString());

                    const rasio = totalPasien > 0 ? (totalParameter / totalPasien).toFixed(1) : '0';
                    $('#kpiRasioItem').text(rasio);

                    // Completion percentage
                    const selesaiPercent = totalPermintaan > 0 ? ((totalSelesai / totalPermintaan) * 100).toFixed(1) : '0';
                    $('#kpiSelesaiPercent').text(selesaiPercent + '% Selesai');
                    $('#barSelesaiProgress').css('width', selesaiPercent + '%');
                    $('#kpiSelesaiLabel').text(totalSelesai + ' dari ' + totalPermintaan + ' Order');

                    // Process percentage
                    const prosesPercent = totalPermintaan > 0 ? ((totalProses / totalPermintaan) * 100).toFixed(1) : '0';
                    $('#kpiProsesPercent').text(prosesPercent + '% Total');
                    $('#barProsesProgress').css('width', prosesPercent + '%');

                    // Rajal vs Ranap Breakdown
                    const rajalSelesai = parseInt(rajalRanap?.rajal?.pemeriksaan_selesai || 0);
                    const rajalBelum = parseInt(rajalRanap?.rajal?.pemeriksaan_belum_selesai || 0);
                    const rajalPasien = parseInt(rajalRanap?.rajal?.jumlah_pasien || 0);

                    const ranapSelesai = parseInt(rajalRanap?.ranap?.pemeriksaan_selesai || 0);
                    const ranapBelum = parseInt(rajalRanap?.ranap?.pemeriksaan_belum_selesai || 0);
                    const ranapPasien = parseInt(rajalRanap?.ranap?.jumlah_pasien || 0);

                    $('#kpiRajalSub').text(`Rawat Jalan: ${rajalPasien}`);
                    $('#kpiRanapSub').text(`Rawat Inap: ${ranapPasien}`);

                    const totalRajalRanapPasien = rajalPasien + ranapPasien;
                    if (totalRajalRanapPasien > 0) {
                        const pctRajal = ((rajalPasien / totalRajalRanapPasien) * 100).toFixed(0);
                        const pctRanap = 100 - pctRajal;
                        $('#barRajal').css('width', pctRajal + '%');
                        $('#barRanap').css('width', pctRanap + '%');
                    }

                    // 2. Donut Charts Update
                    $('#selesaiDataRajal').text(`Selesai: ${rajalSelesai}`);
                    $('#belumSelesaiDataRajal').text(`Belum: ${rajalBelum}`);
                    chartRawatJalan.data.datasets[0].data = [rajalSelesai, rajalBelum];
                    chartRawatJalan.update();

                    $('#selesaiDataRanap').text(`Selesai: ${ranapSelesai}`);
                    $('#belumSelesaiDataRanap').text(`Belum: ${ranapBelum}`);
                    chartRawatInap.data.datasets[0].data = [ranapSelesai, ranapBelum];
                    chartRawatInap.update();

                    // 3. TAT Update
                    const tatData = res.averageTAT || [];
                    const tatLabels = tatData.map(d => d.test_group_name || d.od_test_grp);
                    const tatValues = tatData.map(d => parseInt(d.avg_tat_minutes || 0));

                    chartAverageTAT.data.labels = tatLabels;
                    chartAverageTAT.data.datasets[0].data = tatValues;
                    chartAverageTAT.update();

                    if (tatValues.length > 0) {
                        const totalTatMinutes = tatValues.reduce((a, b) => a + b, 0);
                        const avgTat = Math.round(totalTatMinutes / tatValues.length);
                        $('#tatOverallAvg').text(`${avgTat} Menit`);

                        let minVal = Infinity, maxTests = -1, fastestName = '-', heaviestName = '-';
                        tatData.forEach(d => {
                            let mins = parseInt(d.avg_tat_minutes || 0);
                            let tests = parseInt(d.total_tests || 0);
                            if (mins < minVal) { minVal = mins; fastestName = (d.test_group_name || d.od_test_grp) + ` (${mins}m)`; }
                            if (tests > maxTests) { maxTests = tests; heaviestName = (d.test_group_name || d.od_test_grp) + ` (${tests} uji)`; }
                        });
                        $('#tatFastest').text(fastestName);
                        $('#tatHeaviest').text(heaviestName);
                    }

                    // 4. Spesimen Update
                    const spesimen = res.distribusiSpesimen || [];
                    chartDistribusiSpesimen.data.labels = spesimen.map(d => d.sample || d.specimen_type);
                    chartDistribusiSpesimen.data.datasets[0].data = spesimen.map(d => parseInt(d.total || 0));
                    chartDistribusiSpesimen.update();

                    // 5. Kelompok Pemeriksaan Update
                    const kelompok = res.distribusiPemeriksaan || [];
                    chartDistribusiPemeriksaan.data.labels = kelompok.map(d => d.test_group_name || d.test_group_code);
                    chartDistribusiPemeriksaan.data.datasets[0].data = kelompok.map(d => parseInt(d.total || 0));
                    chartDistribusiPemeriksaan.update();

                    // 6. Top 8 Pemeriksaan
                    const top8 = res.permintaanPemeriksaan || [];
                    chartPermintaanPemeriksaan.data.labels = top8.map(d => d.pemeriksaan);
                    chartPermintaanPemeriksaan.data.datasets[0].data = top8.map(d => parseInt(d.pemeriksaan_selesai || 0));
                    chartPermintaanPemeriksaan.data.datasets[1].data = top8.map(d => parseInt(d.pemeriksaan_belum_selesai || 0));
                    chartPermintaanPemeriksaan.update();

                    // 7. Permintaan Per Waktu
                    const perWaktu = res.permintaanPerWaktu || [];
                    chartPermintaanPerWaktu.data.labels = perWaktu.map(d => d.hour);
                    const totals = perWaktu.map(d => parseInt(d.total_keseluruhan || 0));
                    chartPermintaanPerWaktu.data.datasets[0].data = totals;
                    chartPermintaanPerWaktu.data.datasets[1].data = perWaktu.map(d => parseInt(d.rajal || 0));
                    chartPermintaanPerWaktu.data.datasets[2].data = perWaktu.map(d => parseInt(d.ranap || 0));
                    chartPermintaanPerWaktu.update();

                    // Detect peak hour
                    if (perWaktu.length > 0) {
                        let maxVal = -1, peakHour = '08:00';
                        perWaktu.forEach(d => {
                            let tot = parseInt(d.total_keseluruhan || 0);
                            if (tot > maxVal) { maxVal = tot; peakHour = d.hour; }
                        });
                        $('#peakHourBadge').text(`Puncak: ${peakHour} (${maxVal} Order)`);
                    }

                    // 8. Nilai Kritis Table
                    $('#criticalBadgeCount').text(`${criticals.length} Kasus`);
                    let criticalHtml = '';
                    if (criticals.length === 0) {
                        criticalHtml = '<div class="py-8 text-center text-slate-400 text-xs">Tidak ada nilai kritis aktif ditemukan pada periode ini.</div>';
                    } else {
                        criticals.forEach(c => {
                            const flagClass = c.od_tr_flag === 'HH' ? 'badge-flag-hh' : 'badge-flag-ll';
                            criticalHtml += `
                                <div class="px-3 py-2 grid grid-cols-12 gap-1 items-center hover:bg-rose-50/70 transition-colors">
                                    <div class="col-span-4 min-w-0">
                                        <a href="${c.detail_url}" target="_blank" class="font-bold text-slate-900 hover:text-blue-700 hover:underline block leading-tight truncate text-xs">${c.oh_last_name || '-'}</a>
                                        <span class="text-[10px] font-mono text-slate-500 block">RM: ${c.oh_pid || '-'}</span>
                                    </div>
                                    <div class="col-span-3 min-w-0">
                                        <span class="text-xs font-semibold text-slate-800 block truncate">${c.ti_name || '-'}</span>
                                        <span class="text-[10px] text-slate-500 block truncate">${c.clinic_desc || '-'}</span>
                                    </div>
                                    <div class="col-span-2 text-right">
                                        <span class="font-mono font-black text-xs text-rose-700 block">${c.od_tr_val || '-'}</span>
                                    </div>
                                    <div class="col-span-1 text-center">
                                        <span class="${flagClass}">${c.od_tr_flag}</span>
                                    </div>
                                    <div class="col-span-2 text-right">
                                        <a href="${c.detail_url}" target="_blank" class="inline-block px-2 py-0.5 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100 transition-colors">Lihat &rarr;</a>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    $('#nilai-kritis-container').html(criticalHtml);
                },
                error: function(xhr, status, error) {
                    console.error("Dashboard error:", status, error);
                },
                complete: function() {
                    btnText.text('Tampilkan');
                    btn.prop('disabled', false).removeClass('opacity-60');
                }
            });
        }

        // Initial Load
        fetchDashboardData(false);

        // Fetch on Click
        $('#fetchDataButton').on('click', function() {
            fetchDashboardData(false);
        });

        // Force Refresh on Click
        $('#refreshCacheButton').on('click', function() {
            fetchDashboardData(true);
        });
    });
</script>