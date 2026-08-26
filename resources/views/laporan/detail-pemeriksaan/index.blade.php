@section('title', 'Laporan Detail Pemeriksaan Pasien')

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
                            <h1 class="text-base font-bold text-slate-900">Laporan Detail Pemeriksaan Pasien</h1>
                            <p class="text-xs text-slate-500 mt-0.5">Daftar riwayat transaksi uji per pasien dengan nomor rekam medis, hasil kuantitatif, dan flag.</p>
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

            <!-- Table Card -->
            <div class="bg-white border border-slate-200 rounded p-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-200 pb-3 mb-3">
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Log Transaksi Pemeriksaan</h2>
                        <p class="text-[11px] text-slate-500">Maksimal 500 baris terkini ditampilkan di tabel (gunakan ekspor Excel untuk data lengkap).</p>
                    </div>
                    <input type="text" id="filter-detail-input" placeholder="Cari nama / RM / uji..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-blue-600 w-full sm:w-64">
                </div>

                <div class="overflow-x-auto">
                    <table id="detailTable" class="w-full border border-slate-200 text-xs text-left border-collapse">
                        <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3 w-10 text-center">No</th>
                                <th class="py-2.5 px-3 w-24">Tanggal</th>
                                <th class="py-2.5 px-3 w-28">No. RM / Order</th>
                                <th class="py-2.5 px-3">Nama Pasien</th>
                                <th class="py-2.5 px-3 w-16 text-center">JK / Umur</th>
                                <th class="py-2.5 px-3">Ruangan</th>
                                <th class="py-2.5 px-3">Kelompok Uji</th>
                                <th class="py-2.5 px-3">Pemeriksaan</th>
                                <th class="py-2.5 px-3 text-right">Hasil</th>
                                <th class="py-2.5 px-3 text-center">Flag</th>
                            </tr>
                        </thead>
                        <tbody id="body-detail" class="divide-y divide-slate-200 text-slate-800">
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
            let skelHtml = '';
            for (let i = 0; i < 6; i++) {
                skelHtml += `
                    <tr class="animate-pulse">
                        <td class="py-2.5 px-3 text-center"><div class="h-3 w-4 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-16 bg-slate-200 rounded"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-20 bg-slate-200 rounded"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-32 bg-slate-200 rounded"></div></td>
                        <td class="py-2.5 px-3 text-center"><div class="h-3 w-8 bg-slate-200 rounded mx-auto"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-20 bg-slate-200 rounded"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-16 bg-slate-200 rounded"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-24 bg-slate-200 rounded"></div></td>
                        <td class="py-2.5 px-3 text-right"><div class="h-3 w-10 bg-slate-200 rounded ml-auto"></div></td>
                        <td class="py-2.5 px-3 text-center"><div class="h-4 w-8 bg-slate-200 rounded mx-auto"></div></td>
                    </tr>
                `;
            }
            $('#body-detail').html(skelHtml);
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
                url: "{{ route('laporan.detail-pemeriksaan.data', [], false) }}",
                type: "GET",
                data: { 
                    start_date: startDate, 
                    end_date: endDate,
                    refresh: forceRefresh ? 1 : 0
                },
                success: function(data) {
                    $('#cache-time').text(new Date().toLocaleTimeString('id-ID'));

                    let tbHtml = '';
                    if (data.length === 0) {
                        tbHtml = '<tr><td colspan="10" class="p-6 text-center text-slate-400">Tidak ada data transaksi pada periode ini.</td></tr>';
                    } else {
                        data.forEach((row, idx) => {
                            const flagClass = row.flag === 'HH' ? 'badge-flag-hh' : (row.flag === 'LL' ? 'badge-flag-ll' : (row.flag ? 'px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700' : ''));
                            const dateStr = row.transaction_date ? new Date(row.transaction_date).toLocaleDateString('id-ID') : '-';

                            tbHtml += `
                                <tr class="hover:bg-slate-50 row-detail">
                                    <td class="py-2 px-3 text-center text-slate-400">${idx + 1}</td>
                                    <td class="py-2 px-3 text-slate-600 whitespace-nowrap">${dateStr}</td>
                                    <td class="py-2 px-3 font-mono">
                                        <span class="font-bold text-slate-900 block search-field">${row.patient_id || '-'}</span>
                                        <span class="text-[10px] text-slate-400 block">${row.transaction_number || '-'}</span>
                                    </td>
                                    <td class="py-2 px-3 font-semibold text-slate-900 search-field">${row.patient_name || '-'}</td>
                                    <td class="py-2 px-3 text-center text-slate-600">${row.gender || '-'}/${row.age || '-'}</td>
                                    <td class="py-2 px-3 text-slate-600 search-field">${row.clinic_name || '-'}</td>
                                    <td class="py-2 px-3 font-medium text-slate-700">${row.group_name || '-'}</td>
                                    <td class="py-2 px-3 font-semibold text-slate-800 search-field">${row.test_name || '-'}</td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">${row.result_value || '-'}</td>
                                    <td class="py-2 px-3 text-center whitespace-nowrap">${row.flag ? `<span class="${flagClass}">${row.flag}</span>` : '-'}</td>
                                </tr>
                            `;
                        });
                    }
                    $('#body-detail').html(tbHtml);
                },
                error: function(xhr, status, err) {
                    console.error("Laporan error:", err);
                    $('#body-detail').html('<tr><td colspan="10" class="p-6 text-center text-rose-500">Gagal memuat data laporan detail pemeriksaan.</td></tr>');
                },
                complete: function() {
                    btnText.text('Tampilkan');
                    btn.prop('disabled', false).removeClass('opacity-60');
                }
            });
        }

        // Search in table
        $('#filter-detail-input').on('keyup', function() {
            const query = $(this).val().toLowerCase();
            $('.row-detail').each(function() {
                const text = $(this).find('.search-field').text().toLowerCase();
                if (text.includes(query) || query === '') {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        fetchReportData(false);

        $('#search-button').on('click', () => fetchReportData(false));
        $('#refresh-button').on('click', () => fetchReportData(true));

        $('#export-excel-button').on('click', function() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            window.location.href = `{{ route('laporan.detail-pemeriksaan.export-excel', [], false) }}?start_date=${start}&end_date=${end}`;
        });
    });
</script>