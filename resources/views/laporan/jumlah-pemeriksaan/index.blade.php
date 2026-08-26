@section('title', 'Laporan Jumlah Pemeriksaan')

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
                            <h1 class="text-base font-bold text-slate-900">Laporan Jumlah Pemeriksaan</h1>
                            <p class="text-xs text-slate-500 mt-0.5">Akumulasi volume permintaan uji laboratorium per kelompok tes dan per unit layanan.</p>
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

            <!-- Summary KPI Grid (3 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-white border border-slate-200 rounded p-4">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Pemeriksaan</span>
                    <div class="text-2xl font-black text-slate-900 font-mono mt-1" id="kpiTotalUji">-</div>
                </div>
                <div class="bg-white border border-slate-200 rounded p-4">
                    <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Rawat Jalan</span>
                    <div class="text-2xl font-black text-blue-700 font-mono mt-1" id="kpiTotalRajal">-</div>
                </div>
                <div class="bg-white border border-slate-200 rounded p-4">
                    <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Rawat Inap</span>
                    <div class="text-2xl font-black text-emerald-700 font-mono mt-1" id="kpiTotalRanap">-</div>
                </div>
            </div>

            <!-- Content Area: Tables Grouped by Sub-Lab -->
            <div class="bg-white border border-slate-200 rounded p-4 space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-200 pb-3">
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rincian Volume Permintaan per Kelompok Uji</h2>
                        <p class="text-[11px] text-slate-500">Daftar akumulasi kuantitas per parameter pemeriksaan laboratorium.</p>
                    </div>
                    <input type="text" id="filter-table-input" placeholder="Cari nama pemeriksaan..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-blue-600 w-full sm:w-64">
                </div>

                <div id="test-group-section" class="space-y-6">
                    <!-- Skeleton Loader -->
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
                } else if (preset === 'this_year') {
                    fromDate = new Date(toDate.getFullYear(), 0, 1);
                }

                document.getElementById('start_date').value = fromDate.toISOString().split('T')[0];
                document.getElementById('end_date').value = toDate.toISOString().split('T')[0];
                fetchReportData(false);
            });
        });

        function showSkeletons() {
            $('#kpiTotalUji, #kpiTotalRajal, #kpiTotalRanap').html(
                '<span class="inline-block h-6 w-24 bg-slate-200 animate-pulse rounded"></span>'
            );

            let skelHtml = '';
            for (let g = 0; g < 2; g++) {
                skelHtml += `
                    <div class="border border-slate-200 rounded overflow-hidden animate-pulse">
                        <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex justify-between items-center">
                            <div class="h-4 w-36 bg-slate-200 rounded"></div>
                            <div class="h-3 w-20 bg-slate-200 rounded"></div>
                        </div>
                        <div class="p-3 space-y-2">
                            <div class="h-4 bg-slate-100 rounded w-full"></div>
                            <div class="h-4 bg-slate-100 rounded w-full"></div>
                            <div class="h-4 bg-slate-100 rounded w-full"></div>
                        </div>
                    </div>
                `;
            }
            $('#test-group-section').html(skelHtml);
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
                url: "{{ route('laporan.jumlah-pemeriksaan.data') }}",
                type: "GET",
                data: { 
                    start_date: startDate, 
                    end_date: endDate,
                    refresh: forceRefresh ? 1 : 0
                },
                success: function(res) {
                    $('#cache-time').text(res.cached_at || 'Baru saja');
                    const raw = res.raw || [];

                    const grouped = {};
                    let totalUji = 0, totalRajal = 0, totalRanap = 0;

                    raw.forEach(r => {
                        const grp = r.test_group_name || 'Lain-lain';
                        const test = r.test_name || r.test_code;
                        const code = r.test_code || '';
                        const type = r.jenis_rawat || 'Lainnya';
                        const count = parseInt(r.total || 0);

                        totalUji += count;
                        if (type === 'Rawat Jalan') totalRajal += count;
                        if (type === 'Rawat Inap') totalRanap += count;

                        if (!grouped[grp]) grouped[grp] = {};
                        if (!grouped[grp][test]) {
                            grouped[grp][test] = { code: code, rajal: 0, ranap: 0, total: 0 };
                        }

                        if (type === 'Rawat Jalan') grouped[grp][test].rajal += count;
                        else if (type === 'Rawat Inap') grouped[grp][test].ranap += count;
                        grouped[grp][test].total += count;
                    });

                    $('#kpiTotalUji').text(totalUji.toLocaleString());
                    $('#kpiTotalRajal').text(totalRajal.toLocaleString());
                    $('#kpiTotalRanap').text(totalRanap.toLocaleString());

                    renderTables(grouped);
                },
                error: function(xhr, status, err) {
                    console.error("Laporan error:", err);
                    $('#test-group-section').html('<div class="p-8 text-center text-rose-500 text-xs">Gagal memuat data laporan.</div>');
                },
                complete: function() {
                    btnText.text('Tampilkan');
                    btn.prop('disabled', false).removeClass('opacity-60');
                }
            });
        }

        function renderTables(grouped) {
            const container = $('#test-group-section');
            container.empty();

            const grpKeys = Object.keys(grouped).sort();
            if (grpKeys.length === 0) {
                container.html('<div class="p-8 text-center text-slate-400 text-xs">Tidak ada data pemeriksaan pada periode yang dipilih.</div>');
                return;
            }

            grpKeys.forEach(grpName => {
                const tests = grouped[grpName];
                const testKeys = Object.keys(tests).sort();

                let subRajal = 0, subRanap = 0, subTotal = 0;

                let html = `
                    <div class="border border-slate-200 rounded overflow-hidden group-box">
                        <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">${grpName}</span>
                            <span class="text-[11px] font-semibold text-slate-500">${testKeys.length} Parameter Uji</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left border-collapse item-table">
                                <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                    <tr>
                                        <th class="py-2 px-3 w-12 text-center">No</th>
                                        <th class="py-2 px-3 w-28">Kode Uji</th>
                                        <th class="py-2 px-3">Nama Pemeriksaan</th>
                                        <th class="py-2 px-3 w-28 text-right">Rawat Jalan</th>
                                        <th class="py-2 px-3 w-28 text-right">Rawat Inap</th>
                                        <th class="py-2 px-3 w-28 text-right bg-slate-200">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                `;

                let no = 1;
                testKeys.forEach(tName => {
                    const item = tests[tName];
                    subRajal += item.rajal;
                    subRanap += item.ranap;
                    subTotal += item.total;

                    html += `
                        <tr class="hover:bg-slate-50 row-item">
                            <td class="py-2 px-3 text-center text-slate-400">${no++}</td>
                            <td class="py-2 px-3 font-mono font-medium text-slate-600">${item.code}</td>
                            <td class="py-2 px-3 font-semibold text-slate-900 search-target">${tName}</td>
                            <td class="py-2 px-3 text-right font-mono">${item.rajal.toLocaleString()}</td>
                            <td class="py-2 px-3 text-right font-mono">${item.ranap.toLocaleString()}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold bg-slate-50">${item.total.toLocaleString()}</td>
                        </tr>
                    `;
                });

                html += `
                        <tr class="bg-slate-100 font-bold border-t border-slate-300">
                            <td colspan="3" class="py-2 px-3 text-left">Subtotal ${grpName}</td>
                            <td class="py-2 px-3 text-right font-mono">${subRajal.toLocaleString()}</td>
                            <td class="py-2 px-3 text-right font-mono">${subRanap.toLocaleString()}</td>
                            <td class="py-2 px-3 text-right font-mono font-black bg-slate-200">${subTotal.toLocaleString()}</td>
                        </tr>
                    </tbody>
                    </table>
                    </div>
                </div>
                `;

                container.append(html);
            });
        }

        // Live table search
        $('#filter-table-input').on('keyup', function() {
            const query = $(this).val().toLowerCase();
            $('.group-box').each(function() {
                let matchCount = 0;
                $(this).find('.row-item').each(function() {
                    const text = $(this).find('.search-target').text().toLowerCase();
                    if (text.includes(query)) {
                        $(this).show();
                        matchCount++;
                    } else {
                        $(this).hide();
                    }
                });
                if (matchCount > 0 || query === '') $(this).show();
                else $(this).hide();
            });
        });

        fetchReportData(false);

        $('#search-button').on('click', () => fetchReportData(false));
        $('#refresh-button').on('click', () => fetchReportData(true));

        $('#export-excel-button').on('click', function() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            window.location.href = `{{ route('laporan.jumlah-pemeriksaan.export-excel') }}?start_date=${start}&end_date=${end}`;
        });
    });
</script>
