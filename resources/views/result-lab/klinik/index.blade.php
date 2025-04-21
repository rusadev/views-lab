<title>@yield('title', 'Laboratorium Patologi Klinik')</title>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-blue-500 to-blue-700 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <svg class="w-6 h-6 text-white animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h6m4 12H5a2 2 0 01-2-2V5a2 2 0 012-2h9l5 5v12a2 2 0 01-2 2z" />
            </svg>
            {{ __('Hasil Pemeriksaan Laboratorium Patologi Klinik') }}
        </h2>
    </x-slot>
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4 text-gray-900">
                    <!-- Form Pencarian -->
                    <form method="GET" action="#" class="space-y-4">
                        <div>
                            <label class="font-bold bg-gradient-to-r from-blue-500 to-blue-800 text-transparent bg-clip-text">
                                <i class="fas fa-filter"></i> Pencarian Berdasarkan:
                            </label>
                            <hr class="my-2">
                            <div class="flex gap-4 mt-1">
                                <label class="flex items-center text-sm font-medium hover:text-blue-600 transition">
                                    <input type="radio" name="search_type" value="rm" class="form-radio text-blue-600 focus:ring-blue-500" checked>
                                    <span class="ml-2">
                                        <i class="fas fa-id-card"></i> Nomor RM
                                    </span>
                                </label>
                                <label class="flex items-center text-sm font-medium hover:text-green-600 transition">
                                    <input type="radio" name="search_type" value="ruangan" class="form-radio text-green-600 focus:ring-green-500">
                                    <span class="ml-2">
                                        <i class="fas fa-hospital"></i> Ruangan
                                    </span>
                                </label>
                            </div>
                        </div>


                        <!-- Input Nomor RM dan Ruangan -->
                        <div class="flex flex-wrap gap-4 items-end">
                            <!-- Nomor RM -->
                            <div id="rm_section" class="w-1/2">
                                <label for="rm_number" class="block text-sm font-medium mb-1">Nomor RM</label>
                                <input type="text" id="rm_number" name="rm_number" class="w-full p-2 border rounded text-sm" placeholder="Masukan Nomor RM Pasien...">
                            </div>

                            <!-- Ruangan -->
                            <div id="ruangan_section" class="hidden w-1/4">
                                <label for="ruangan" class="block text-sm font-medium mb-1 select2">Ruangan</label>
                                <select id="ruangan" class="w-full border rounded text-sm">
                                    <option selected>Pilih Ruangan</option>

                                    @foreach ($ruangans as $r)
                                    <option value="{{$r->clinic_code}}">{{$r->clinic_desc}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Tanggal Mulai & Tanggal Selesai -->
                            <div id="date_range_section" class="hidden flex w-1/4 gap-2">
                                <div class="w-1/2">
                                    <label for="start_date" class="block text-sm font-medium mb-1">Tanggal Mulai</label>
                                    <input type="date" id="start_date" class="w-full p-2 border rounded text-sm">
                                </div>
                                <div class="w-1/2">
                                    <label for="end_date" class="block text-sm font-medium mb-1">Tanggal Selesai</label>
                                    <input type="date" id="end_date" class="w-full p-2 border rounded text-sm">
                                </div>
                            </div>
                        </div>

                        <div>
                            <button id="search-button" class="bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 text-white text-sm font-semibold px-3 py-2 rounded flex items-center gap-2 shadow-lg transition-all duration-300">
                                <i id="search-icon" class="fas fa-search"></i>
                                <span id="search-text">Cari Hasil Pemeriksaan</span>
                            </button>

                        </div>
                    </form>
                </div>
            </div>

            <!-- Informasi Order -->
            <div class="grid grid-cols-12 gap-4 mt-4">
                <!-- Card 1 (8 columns) -->
                <div class="col-span-8 bg-white shadow-sm rounded-lg">
                    <div class="p-4 text-gray-900 relative">
                        <div id="loading-overlay-order" class="hidden absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 z-10">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                                <span class="text-gray-700 font-semibold">Memuat Data...</span>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <!-- <span id="criticalTableTitle" class="text-xl font-bold">Daftar Pemeriksaan Laboratorium</span> -->
                            <span id="criticalTableTitle" class="text-xl font-bold bg-gradient-to-r from-blue-500 to-blue-800 text-transparent bg-clip-text">
                                Daftar Pemeriksaan Laboratorium
                            </span>
                            <hr class="my-2">

                            <table id="orderTable" class="w-full border text-sm relative z-0 min-w-max">
                                <thead>
                                    <tr class="bg-grey-400">
                                        <th class="border px-2 py-1">Status Pemeriksaan </th>
                                        <th class="border px-2 py-1">Tanggal Permintaan</th>
                                        <th class="border px-2 py-1">Nomor SIMRS</th>
                                        <th class="border px-2 py-1">Nomor LAB</th>
                                        <th class="border px-2 py-1">Nomor RM</th>
                                        <th class="border px-2 py-1">Nama Pasien</th>
                                        <th class="border px-2 py-1">Ruangan</th>
                                        <th class="border px-2 py-1">Dokter Pengirim</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Card 2 (4 columns) -->
                <div class="col-span-4 bg-white shadow-sm rounded-lg">
                    <div class="p-4 text-gray-900 relative">
                        <div id="loading-overlay-kritis" class="hidden absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 z-10">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                                <span class="text-gray-700 font-semibold">Memuat Data...</span>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <span id="criticalTableTitle" class="text-xl font-bold bg-gradient-to-r from-red-500 to-red-700 text-transparent bg-clip-text flex items-center gap-2 animate-pulse">
                                <i class="fas fa-exclamation-triangle"></i> Nilai Kritis
                            </span>
                            <hr class="my-2">


                            <table id="criticalTable" class="w-full relative z-0 min-w-max border">
                                <thead>
                                    <tr class="bg-grey-500 font-bold">
                                        <th class="border px-2 py-1">Nama Pasien</th>
                                        <th class="border px-2 py-1">Pemeriksaan</th>
                                        <th class="border px-2 py-1">Flag</th>
                                        <th class="border px-2 py-1">Hasil</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>


        </div>
    </div>
</x-app-layout>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.tailwindcss.js"></script>


<script>
    document.addEventListener("DOMContentLoaded", function() {

        const now = new Date();
        const oneMonthAgo = new Date();
        oneMonthAgo.setMonth(now.getMonth() - 1);
        document.getElementById("start_date").valueAsDate = oneMonthAgo;
        document.getElementById("end_date").valueAsDate = now;

        const searchTypeRadios = document.querySelectorAll("input[name='search_type']");
        const rmSection = document.getElementById("rm_section");
        const ruanganSection = document.getElementById("ruangan_section");
        const dateRangeSection = document.getElementById("date_range_section");

        searchTypeRadios.forEach(radio => {
            radio.addEventListener("change", function() {
                if (this.value === "rm") {
                    rmSection.classList.remove("hidden");
                    ruanganSection.classList.add("hidden");
                    dateRangeSection.classList.add("hidden");
                } else {
                    rmSection.classList.add("hidden");
                    ruanganSection.classList.remove("hidden");
                    dateRangeSection.classList.remove("hidden");
                }
            });
        });

        $('#ruangan').select2({
            width: '100%',
        });

        

        let table = $('#orderTable').DataTable({
            processing: true,
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
            columns: [{
                    data: 'oh_ord_status',
                    name: 'oh_ord_status',
                    className: 'text-center',
                },
                {
                    data: 'oh_trx_dt',
                    name: 'oh_trx_dt',
                    searchable: false
                },
                {
                    data: 'oh_ono',
                    name: 'oh_ono'
                },
                {
                    data: 'oh_tno',
                    name: 'oh_tno'
                },
                {
                    data: 'oh_pid',
                    name: 'oh_pid',
                    searchable: true,
                    render: function(data, type, row) {
                        return `<span class="font-bold">${data}</span>`;
                    }
                },
                {
                    data: 'oh_last_name',
                    name: 'oh_last_name',
                    searchable: true,
                    render: function(data, type, row) {
                        return `<span class="font-bold">${data}</span>`;
                    }
                },
                {
                    data: 'clinic_desc',
                    name: 'clinic_desc',
                    searchable: false
                },
                {
                    data: 'oh_dname',
                    name: 'oh_dname',
                    searchable: false
                },

            ],
            order: [],
            responsive: true,
            createdRow: function(row, data, dataIndex) {
                $(row).addClass('hover:bg-gray-200 transition text-sm');
                $('td', row).addClass('border px-2 py-1');
            },
            lengthChange: false,
            searching: true,
            paging: true,
            deferLoading: 0,
            ordering: false
        });

        let criticalTable = $('#criticalTable').DataTable({
            processing: true,
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
            columns: [{
                    data: 'patient_info',
                    name: 'patient_info',
                    searchable: false
                },
                {
                    data: 'test_name',
                    name: 'test_name',
                    searchable: false
                },
                {
                    data: 'result',
                    name: 'result',
                    searchable: true
                },
                {
                    data: 'critical_status',
                    name: 'critical_status',
                    searchable: true
                }
            ],
            columnDefs: [{
                    targets: [0],
                    className: "wrap-text"
                }, // Terapkan ke Nama Pasien
                {
                    targets: "_all",
                    className: "text-left"
                }
            ],
            order: [],
            responsive: true,
            scrollY: '50vh',
            scrollCollapse: true, // Aktifkan scroll jika data sedikit
            createdRow: function(row, data, dataIndex) {
                $(row).addClass('hover:bg-gray-200 transition text-sm');
                $('td', row).addClass('border px-2 py-1');
            },
            lengthChange: false,
            searching: false,
            paging: false,
            deferLoading: 0,
            ordering: false,
            info: false,
        });


        $("#search-button").on("click", function() {
            let button = $(this);
            let icon = $("#search-icon");
            let text = $("#search-text");
            let overlay = $("#loading-overlay-order");
            let kritis = $("#loading-overlay-kritis");

            // Menampilkan overlay
            overlay.removeClass("hidden").fadeIn(200);
            kritis.removeClass("hidden").fadeIn(200);

            // Mengubah ikon dan status tombol
            icon.removeClass("fa-search").addClass("fa-spinner fa-spin");
            text.text("Mencari...");
            button.prop("disabled", true).addClass("opacity-50 cursor-not-allowed");

            let tableDeferred = $.Deferred();
            let criticalTableDeferred = $.Deferred();

            table.ajax.reload(function() {
                tableDeferred.resolve();
            });

            criticalTable.ajax.reload(function() {
                criticalTableDeferred.resolve();
            });

            $.when(tableDeferred, criticalTableDeferred).done(function() {
                icon.removeClass("fa-spinner fa-spin").addClass("fa-search");
                text.text("Cari Hasil Pemeriksaan");
                button.prop("disabled", false).removeClass("opacity-50 cursor-not-allowed");

                overlay.fadeOut(200);
                kritis.fadeOut(200);
            });
        });


    });
</script>