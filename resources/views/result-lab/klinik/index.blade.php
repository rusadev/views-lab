@section('title', 'Laboratorium Patologi Klinik')

<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            
            <!-- Page Header Card (Flat) -->
            <div class="bg-white border border-slate-200 rounded p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-bold text-slate-900">Hasil Pemeriksaan Patologi Klinik</h1>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 text-slate-700 border border-slate-300">v2.0</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Pencarian data order, status validasi hasil, dan pemantauan nilai kritis laboratorium.</p>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-1 rounded bg-emerald-50 text-emerald-800 font-bold border border-emerald-300">
                        Online
                    </span>
                </div>
            </div>

            <!-- Filter & Search Panel (Flat) -->
            <div class="bg-white border border-slate-200 rounded">
                <div class="border-b border-slate-200 px-4 py-3 bg-slate-50 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        Parameter Pencarian
                    </div>

                    <!-- Segmented Control Switch (Flat) -->
                    <div class="inline-flex border border-slate-300 rounded overflow-hidden text-xs font-semibold bg-slate-100">
                        <label class="cursor-pointer">
                            <input type="radio" name="search_type" value="rm" class="sr-only peer" checked>
                            <span class="inline-block px-3.5 py-1.5 text-slate-600 peer-checked:bg-blue-600 peer-checked:text-white transition-colors">
                                Pasien (No. RM / Nama)
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="search_type" value="ruangan" class="sr-only peer">
                            <span class="inline-block px-3.5 py-1.5 text-slate-600 peer-checked:bg-blue-600 peer-checked:text-white transition-colors border-l border-slate-300">
                                Ruangan & Tanggal
                            </span>
                        </label>
                    </div>
                </div>

                <div class="p-4">
                    <form id="filterForm" onsubmit="return false;">
                        
                        <!-- Mode 1: Pencarian Pasien (Select2) -->
                        <div id="rm_section" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2.5">
                            <div class="flex-1">
                                <label for="rm_number" class="block text-xs font-semibold text-slate-700 mb-1">
                                    Pilih / Cari Pasien (Ketik No. RM atau Nama)
                                </label>
                                <select id="rm_number" name="rm_number" class="w-full">
                                    <option value="" selected>Ketik No. RM atau Nama Pasien untuk mencari...</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="search-button-rm" 
                                        class="h-[38px] px-5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold rounded border border-blue-700 transition-colors whitespace-nowrap">
                                    <span class="search-btn-text">Cari Hasil Pemeriksaan</span>
                                </button>
                                <button type="button" id="reset-button-rm" 
                                        class="h-[38px] px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded border border-slate-300 transition-colors whitespace-nowrap">
                                    Reset
                                </button>
                            </div>
                        </div>

                        <!-- Mode 2: Pencarian Ruangan & Tanggal -->
                        <div id="ruangan_section" class="hidden space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5 items-end">
                                <!-- Ruangan Select2 -->
                                <div class="md:col-span-4">
                                    <label for="ruangan" class="block text-xs font-semibold text-slate-700 mb-1">
                                        Pilih Ruangan / Klinik
                                    </label>
                                    <select id="ruangan" name="ruangan" class="w-full">
                                        <option value="" selected>Semua Ruangan</option>
                                        @foreach ($ruangans as $r)
                                        <option value="{{ $r->clinic_code }}">{{ $r->clinic_desc }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Tanggal Mulai -->
                                <div class="md:col-span-2">
                                    <label for="start_date" class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai</label>
                                    <input type="date" id="start_date" name="start_date" 
                                           class="w-full h-[38px] px-2.5 bg-white border border-slate-300 rounded text-xs text-slate-800 outline-none focus:border-blue-600">
                                </div>

                                <!-- Tanggal Selesai -->
                                <div class="md:col-span-2">
                                    <label for="end_date" class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Selesai</label>
                                    <input type="date" id="end_date" name="end_date" 
                                           class="w-full h-[38px] px-2.5 bg-white border border-slate-300 rounded text-xs text-slate-800 outline-none focus:border-blue-600">
                                </div>

                                <!-- Action Buttons -->
                                <div class="md:col-span-4 flex items-center gap-2">
                                    <button type="button" id="search-button-ruangan" 
                                            class="flex-1 h-[38px] px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold rounded border border-blue-700 transition-colors whitespace-nowrap">
                                        <span class="search-btn-text">Cari Hasil Pemeriksaan</span>
                                    </button>
                                    <button type="button" id="reset-button-ruangan" 
                                            class="h-[38px] px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded border border-slate-300 transition-colors whitespace-nowrap">
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <!-- Quick Date Presets -->
                            <div class="flex items-center gap-2 text-xs pt-0.5">
                                <span class="text-slate-500 font-medium text-[11px]">Rentang Cepat:</span>
                                <button type="button" class="date-preset px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] border border-slate-300 font-medium" data-days="0">Hari Ini</button>
                                <button type="button" class="date-preset px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] border border-slate-300 font-medium" data-days="7">7 Hari Terakhir</button>
                                <button type="button" class="date-preset px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] border border-slate-300 font-medium" data-days="30">30 Hari Terakhir</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Content Grid: Orders Table (8 cols) & Critical Values (4 cols) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                
                <!-- Main Order Table Card (8 cols) -->
                <div class="lg:col-span-8 bg-white border border-slate-200 rounded flex flex-col overflow-hidden">
                    <div class="border-b border-slate-200 px-4 py-3 bg-slate-50 flex items-center justify-between">
                        <div>
                            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Daftar Pemeriksaan Laboratorium</h2>
                        </div>
                    </div>

                    <div class="p-4 relative flex-1">
                        <!-- Loading Overlay -->
                        <div id="loading-overlay-order" class="hidden absolute inset-0 bg-white/90 flex flex-col items-center justify-center z-20">
                            <span class="text-xs font-bold text-slate-800">Memuat Data Pemeriksaan...</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table id="orderTable" class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 border-b border-slate-300 text-slate-700 font-bold uppercase text-[11px]">
                                        <th class="py-3 px-3 text-center whitespace-nowrap">Status</th>
                                        <th class="py-3 px-3 whitespace-nowrap">Tanggal Order</th>
                                        <th class="py-3 px-3 whitespace-nowrap">No. SIMRS</th>
                                        <th class="py-3 px-3 whitespace-nowrap">No. LAB</th>
                                        <th class="py-3 px-3 whitespace-nowrap">No. RM</th>
                                        <th class="py-3 px-3 min-w-[140px]">Nama Pasien</th>
                                        <th class="py-3 px-3 min-w-[120px]">Ruangan</th>
                                        <th class="py-3 px-3 min-w-[120px]">Dokter Pengirim</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-700">
                                    <!-- DataTables Output -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Critical Values Card (4 cols) - HIGH FOCUS HIGHLIGHT -->
                <div class="lg:col-span-4 bg-white border-2 border-rose-400 rounded flex flex-col overflow-hidden">
                    <!-- Highlighted Danger Header -->
                    <div class="bg-rose-600 px-4 py-3 text-white flex items-center justify-between">
                        <div>
                            <h2 class="text-xs font-black uppercase tracking-wider text-white">NILAI KRITIS (CRITICAL)</h2>
                            <p class="text-[11px] text-rose-100 font-medium">Hasil melewati batas kritis (HH / LL)</p>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-black rounded bg-white text-rose-700 border border-rose-200">
                            Perlu Perhatian
                        </span>
                    </div>

                    <div class="p-3 relative flex-1 flex flex-col bg-rose-50/30">
                        <!-- Loading Overlay -->
                        <div id="loading-overlay-kritis" class="hidden absolute inset-0 bg-white/90 flex flex-col items-center justify-center z-20">
                            <span class="text-xs font-bold text-rose-800">Memeriksa Nilai Kritis...</span>
                        </div>

                        <div class="overflow-x-auto flex-1">
                            <table id="criticalTable" class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-rose-100 border-b border-rose-300 text-rose-950 font-bold text-[11px] uppercase">
                                        <th class="py-2.5 px-2.5">Pasien</th>
                                        <th class="py-2.5 px-2">Uji Lab</th>
                                        <th class="py-2.5 px-2 text-center">Flag</th>
                                        <th class="py-2.5 px-2">Hasil</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-rose-200 text-slate-800">
                                    <!-- DataTables Output -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.tailwindcss.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Date Presets Helper
        function setDateRange(daysAgo) {
            const today = new Date();
            const fromDate = new Date();
            fromDate.setDate(today.getDate() - daysAgo);

            document.getElementById("start_date").value = fromDate.toISOString().split('T')[0];
            document.getElementById("end_date").value = today.toISOString().split('T')[0];
        }

        // Initialize default dates (30 days ago)
        setDateRange(30);

        // Date preset buttons
        document.querySelectorAll('.date-preset').forEach(button => {
            button.addEventListener('click', function() {
                const days = parseInt(this.getAttribute('data-days'), 10);
                setDateRange(days);
            });
        });

        // Initialize Select2 for Patient (AJAX Search + Tags support)
        $('#rm_number').select2({
            placeholder: 'Ketik No. RM atau Nama Pasien (contoh: 010497)...',
            allowClear: true,
            width: '100%',
            tags: true,
            minimumInputLength: 1,
            ajax: {
                url: "{{ route('klinik.patient.search') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });

        // Initialize Select2 for Ruangan
        $('#ruangan').select2({
            placeholder: 'Semua Ruangan / Klinik',
            allowClear: true,
            width: '100%'
        });

        // Search Type Toggle
        const searchTypeRadios = document.querySelectorAll("input[name='search_type']");
        const rmSection = document.getElementById("rm_section");
        const ruanganSection = document.getElementById("ruangan_section");

        function updateFilterView(type) {
            if (type === "rm") {
                rmSection.classList.remove("hidden");
                ruanganSection.classList.add("hidden");
            } else {
                rmSection.classList.add("hidden");
                ruanganSection.classList.remove("hidden");
            }
        }

        searchTypeRadios.forEach(radio => {
            radio.addEventListener("change", function() {
                updateFilterView(this.value);
            });
        });

        // Reset Buttons
        $('#reset-button-rm, #reset-button-ruangan').on('click', function() {
            $('#rm_number').val(null).trigger('change');
            $('#ruangan').val(null).trigger('change');
            setDateRange(30);
            table.clear().draw();
            criticalTable.clear().draw();
        });

        // Auto trigger search when patient selected from Select2
        $('#rm_number').on('select2:select', function(e) {
            triggerSearch();
        });

        // DataTables: Order Table
        let table = $('#orderTable').DataTable({
            processing: false,
            serverSide: true,
            ajax: {
                url: "{{ route('klinik.order') }}",
                data: function(d) {
                    d.search_type = $("input[name='search_type']:checked").val();
                    d.rm_number = $("#rm_number").val();
                    d.ruangan = $("#ruangan").val();
                    d.start_date = $("#start_date").val();
                    d.end_date = $("#end_date").val();
                }
            },
            columns: [
                {
                    data: 'oh_ord_status',
                    name: 'oh_ord_status',
                    className: 'text-center align-middle whitespace-nowrap'
                },
                {
                    data: 'oh_trx_dt',
                    name: 'oh_trx_dt',
                    className: 'text-slate-600 font-mono text-[11px] whitespace-nowrap align-middle'
                },
                {
                    data: 'oh_ono',
                    name: 'oh_ono',
                    className: 'text-slate-700 font-mono text-[11px] align-middle'
                },
                {
                    data: 'oh_tno',
                    name: 'oh_tno',
                    className: 'text-slate-700 font-mono text-[11px] align-middle'
                },
                {
                    data: 'oh_pid',
                    name: 'oh_pid',
                    className: 'align-middle',
                    render: function(data, type, row) {
                        return `<span class="font-bold text-slate-900 font-mono text-xs">${data || '-'}</span>`;
                    }
                },
                {
                    data: 'oh_last_name',
                    name: 'oh_last_name',
                    className: 'align-middle',
                    render: function(data, type, row) {
                        return `<span class="font-semibold text-slate-900">${data || '-'}</span>`;
                    }
                },
                {
                    data: 'clinic_desc',
                    name: 'clinic_desc',
                    className: 'text-slate-700 text-xs align-middle'
                },
                {
                    data: 'oh_dname',
                    name: 'oh_dname',
                    className: 'text-slate-700 text-xs align-middle'
                }
            ],
            order: [],
            responsive: true,
            createdRow: function(row, data, dataIndex) {
                $(row).addClass('hover:bg-slate-50 transition-colors');
                $('td', row).addClass('py-3 px-3 border-b border-slate-200 align-middle');
            },
            lengthChange: false,
            searching: true,
            paging: true,
            pageLength: 10,
            deferLoading: 0,
            ordering: false,
            language: {
                emptyTable: "Tidak ada data pemeriksaan ditemukan. Pilih pasien atau tentukan ruangan untuk memulai pencarian.",
                info: "Menampilkan _START_ s/d _END_ dari total _TOTAL_ order",
                infoEmpty: "Menampilkan 0 s/d 0 dari 0 order",
                search: "Saring Hasil:",
                searchPlaceholder: "Ketik teks...",
                paginate: {
                    first: 'Awal',
                    last: 'Akhir',
                    next: 'Berikutnya',
                    previous: 'Sebelumnya'
                }
            }
        });

        // DataTables: Critical Table
        let criticalTable = $('#criticalTable').DataTable({
            processing: false,
            serverSide: true,
            ajax: {
                url: "{{ route('klinik.order.flag') }}",
                data: function(d) {
                    d.search_type = $("input[name='search_type']:checked").val();
                    d.rm_number = $("#rm_number").val();
                    d.ruangan = $("#ruangan").val();
                    d.start_date = $("#start_date").val();
                    d.end_date = $("#end_date").val();
                }
            },
            columns: [
                {
                    data: 'patient_info',
                    name: 'patient_info',
                    className: 'align-top py-2.5 px-2.5'
                },
                {
                    data: 'test_name',
                    name: 'test_name',
                    className: 'align-top py-2.5 px-2'
                },
                {
                    data: 'critical_status',
                    name: 'critical_status',
                    className: 'align-top text-center py-2.5 px-2'
                },
                {
                    data: 'result',
                    name: 'result',
                    className: 'align-top py-2.5 px-2'
                }
            ],
            order: [],
            responsive: true,
            scrollY: '50vh',
            scrollCollapse: true,
            createdRow: function(row, data, dataIndex) {
                $(row).addClass('hover:bg-rose-100/60 transition-colors bg-white');
                $('td', row).addClass('border-b border-rose-200');
            },
            lengthChange: false,
            searching: false,
            paging: false,
            deferLoading: 0,
            ordering: false,
            info: false,
            language: {
                emptyTable: '<div class="py-8 text-center text-slate-500 font-medium text-xs">Tidak ada nilai kritis ditemukan.</div>'
            }
        });

        // Shared Search Function
        function triggerSearch() {
            let overlay = $("#loading-overlay-order");
            let kritis = $("#loading-overlay-kritis");
            let buttons = $("#search-button-rm, #search-button-ruangan");
            let texts = $(".search-btn-text");

            overlay.removeClass("hidden");
            kritis.removeClass("hidden");

            texts.text("Memuat Data...");
            buttons.prop("disabled", true).addClass("opacity-60 cursor-not-allowed");

            let tableDeferred = $.Deferred();
            let criticalTableDeferred = $.Deferred();

            table.ajax.reload(function() {
                tableDeferred.resolve();
            });

            criticalTable.ajax.reload(function() {
                criticalTableDeferred.resolve();
            });

            $.when(tableDeferred, criticalTableDeferred).done(function() {
                texts.text("Cari Hasil Pemeriksaan");
                buttons.prop("disabled", false).removeClass("opacity-60 cursor-not-allowed");

                overlay.addClass("hidden");
                kritis.addClass("hidden");
            });
        }

        // Search Click Handlers
        $("#search-button-rm, #search-button-ruangan").on("click", function() {
            triggerSearch();
        });

    });
</script>