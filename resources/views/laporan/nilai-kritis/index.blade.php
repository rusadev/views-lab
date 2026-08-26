@section('title', 'Laporan Nilai Kritis Laboratorium')

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
                            <h1 class="text-base font-bold text-slate-900">Laporan Nilai Kritis Laboratorium</h1>
                            <p class="text-xs text-slate-500 mt-0.5">Dokumentasi hasil laboratorium yang melewati batas kritis (HH / LL) untuk audit keselamatan pasien.</p>
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
                        <button id="search-button" type="button" class="h-9 px-4 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded border border-rose-700 transition-colors flex items-center gap-1.5">
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

            <!-- KPI Summary Bar -->
            <div class="bg-white border-2 border-rose-200 rounded p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-rose-50/10">
                <div class="space-y-0.5">
                    <span class="text-xs font-bold text-rose-900 uppercase tracking-wider">Total Kasus Nilai Kritis Teridentifikasi</span>
                    <p class="text-[11px] text-slate-500">Pasien dengan hasil di atas batas atas ekstrim (HH) atau di bawah batas bawah ekstrim (LL).</p>
                </div>
                <div class="flex items-center gap-3">
                    <input type="text" id="filter-kritis-input" placeholder="Cari nama / RM / uji..." class="h-8 px-3 text-xs border border-slate-300 rounded outline-none focus:border-rose-600 w-48 sm:w-60">
                    <div class="text-2xl font-black text-rose-700 font-mono" id="kpiTotalKritis">-</div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white border border-slate-200 rounded p-4">
                <div class="border-b border-slate-200 pb-2 mb-3">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Daftar Pasien & Riwayat Hasil Kritis</h2>
                    <p class="text-[11px] text-slate-500">Rincian parameter, waktu verifikasi dokter, dan unit ruangan pengirim.</p>
                </div>
                <div class="overflow-x-auto">
                    <table id="nilaiKritisTable" class="w-full border border-slate-200 text-xs text-left border-collapse">
                        <thead class="bg-rose-100 text-rose-950 font-bold border-b border-rose-200">
                            <tr>
                                <th class="py-2.5 px-3 w-10 text-center">No</th>
                                <th class="py-2.5 px-3 w-24">Tanggal</th>
                                <th class="py-2.5 px-3 w-28">No. RM / Order</th>
                                <th class="py-2.5 px-3">Nama Pasien</th>
                                <th class="py-2.5 px-3">Ruangan</th>
                                <th class="py-2.5 px-3">Pemeriksaan</th>
                                <th class="py-2.5 px-3 text-right">Hasil Kritis</th>
                                <th class="py-2.5 px-3 text-center">Flag</th>
                                <th class="py-2.5 px-3">Waktu Validasi</th>
                                <th class="py-2.5 px-3">Validator</th>
                            </tr>
                        </thead>
                        <tbody id="tableBodynilaiKritis" class="divide-y divide-rose-100 text-slate-800">
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
            $('#kpiTotalKritis').html('<span class="inline-block h-6 w-20 bg-rose-200 animate-pulse rounded"></span>');

            let skelHtml = '';
            for (let i = 0; i < 6; i++) {
                skelHtml += `
                    <tr class="animate-pulse">
                        <td class="py-2.5 px-3 text-center"><div class="h-3 w-4 bg-rose-200 rounded mx-auto"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-16 bg-rose-200 rounded"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-20 bg-rose-200 rounded"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-32 bg-rose-200 rounded"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-24 bg-rose-200 rounded"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-28 bg-rose-200 rounded"></div></td>
                        <td class="py-2.5 px-3 text-right"><div class="h-3 w-12 bg-rose-200 rounded ml-auto"></div></td>
                        <td class="py-2.5 px-3 text-center"><div class="h-4 w-8 bg-rose-200 rounded mx-auto"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-20 bg-rose-200 rounded"></div></td>
                        <td class="py-2.5 px-3"><div class="h-3 w-16 bg-rose-200 rounded"></div></td>
                    </tr>
                `;
            }
            $('#tableBodynilaiKritis').html(skelHtml);
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
                url: "{{ route('laporan.nilai-kritis.data', [], false) }}",
                type: "GET",
                data: { 
                    start_date: startDate, 
                    end_date: endDate,
                    refresh: forceRefresh ? 1 : 0
                },
                success: function(data) {
                    $('#kpiTotalKritis').text(data.length + ' Kasus');

                    let tbHtml = '';
                    if (data.length === 0) {
                        tbHtml = '<tr><td colspan="10" class="p-6 text-center text-slate-400">Tidak ada kasus nilai kritis pada periode yang dipilih.</td></tr>';
                    } else {
                        data.forEach((row, idx) => {
                            const flagClass = row.od_tr_flag === 'HH' ? 'badge-flag-hh' : 'badge-flag-ll';
                            const dateStr = row.oh_trx_dt ? new Date(row.oh_trx_dt).toLocaleDateString('id-ID') : '-';
                            const updateStr = row.od_update_on ? new Date(row.od_update_on).toLocaleString('id-ID') : '-';

                            tbHtml += `
                                <tr class="hover:bg-rose-50/60 row-kritis">
                                    <td class="py-2 px-3 text-center text-slate-400">${idx + 1}</td>
                                    <td class="py-2 px-3 text-slate-600 whitespace-nowrap">${dateStr}</td>
                                    <td class="py-2 px-3 font-mono">
                                        <span class="font-bold text-slate-900 block target-search">${row.oh_pid || '-'}</span>
                                        <span class="text-[10px] text-slate-400 block">${row.oh_tno || '-'}</span>
                                    </td>
                                    <td class="py-2 px-3 font-semibold text-slate-900 target-search">${row.oh_last_name || '-'}</td>
                                    <td class="py-2 px-3 text-slate-600 target-search">${row.clinic_desc || '-'}</td>
                                    <td class="py-2 px-3 font-medium text-slate-800 target-search">${row.ti_name || '-'}</td>
                                    <td class="py-2 px-3 text-right font-mono font-black text-rose-700 text-sm whitespace-nowrap">${row.od_tr_val || '-'}</td>
                                    <td class="py-2 px-3 text-center whitespace-nowrap"><span class="${flagClass}">${row.od_tr_flag}</span></td>
                                    <td class="py-2 px-3 text-[11px] text-slate-500 whitespace-nowrap">${updateStr}</td>
                                    <td class="py-2 px-3 text-[11px] text-slate-600">${row.od_validate_by || '-'}</td>
                                </tr>
                            `;
                        });
                    }
                    $('#tableBodynilaiKritis').html(tbHtml);
                },
                error: function(xhr, status, err) {
                    console.error("Laporan error:", err);
                    $('#tableBodynilaiKritis').html('<tr><td colspan="10" class="p-6 text-center text-rose-500">Gagal memuat data laporan nilai kritis.</td></tr>');
                },
                complete: function() {
                    btnText.text('Tampilkan');
                    btn.prop('disabled', false).removeClass('opacity-60');
                }
            });
        }

        // Search in table
        $('#filter-kritis-input').on('keyup', function() {
            const query = $(this).val().toLowerCase();
            $('.row-kritis').each(function() {
                const text = $(this).find('.target-search').text().toLowerCase();
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
            window.location.href = `{{ route('laporan.nilai-kritis.export-excel', [], false) }}?start_date=${start}&end_date=${end}`;
        });

        $('#export-word-button').on('click', function() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            window.location.href = `{{ route('laporan.nilai-kritis.export-word', [], false) }}?start_date=${start}&end_date=${end}`;
        });
    });
</script>