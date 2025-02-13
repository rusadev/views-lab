<title>@yield('title', 'Laboratorium Patologi Klinik')</title>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
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
                            <label class="block text-sm font-medium text-gray-700">Pencarian Berdasarkan:</label>
                            <div class="flex gap-4 mt-1">
                                <label class="flex items-center text-sm">
                                    <input type="radio" name="search_type" value="rm" class="form-radio" checked>
                                    <span class="ml-2">Nomor RM</span>
                                </label>
                                <label class="flex items-center text-sm">
                                    <input type="radio" name="search_type" value="ruangan" class="form-radio">
                                    <span class="ml-2">Ruangan</span>
                                </label>
                            </div>
                        </div>

                        <!-- Input Nomor RM dan Ruangan -->
                        <div class="flex flex-wrap gap-4 items-end">
                            <!-- Nomor RM -->
                            <div id="rm_section" class="w-1/2">
                                <label for="rm_number" class="block text-sm font-medium text-gray-700 mb-1">Nomor RM</label>
                                <input type="text" id="rm_number" name="rm_number" class="w-full p-2 border rounded text-sm" placeholder="Masukan Nomor RM Pasien...">
                            </div>

                            <!-- Ruangan -->
                            <div id="ruangan_section" class="hidden w-1/4">
                                <label for="ruangan" class="block text-sm font-medium text-gray-700 mb-1 select2">Ruangan</label>
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
                                    <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                                    <input type="date" id="tanggal_mulai" class="w-full p-2 border rounded text-sm">
                                </div>
                                <div class="w-1/2">
                                    <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                                    <input type="date" id="tanggal_selesai" class="w-full p-2 border rounded text-sm">
                                </div>
                            </div>
                        </div>

                        <!-- <div>
                            <button class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold px-3 py-2 rounded">
                                <i class="fas fa-search"></i> Cari Hasil Pemeriksaan
                            </button>
                        </div> -->

                        <div>
                            <button id="search-button" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold px-3 py-2 rounded flex items-center gap-2">
                                <i id="search-icon" class="fas fa-search"></i>
                                <span id="search-text">Cari Hasil Pemeriksaan</span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <!-- Informasi Order -->
            <div class="mt-4 bg-white shadow-sm rounded-lg">
                <div class="p-4 text-gray-900 relative">
                    <div id="loading-overlay" class="hidden absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 z-10">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                            <span class="text-gray-700 font-semibold">Memuat Data...</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto"> <!-- Tambahkan wrapper ini -->
                        <table id="orderTable" class="w-full border text-sm relative z-0 min-w-max">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border px-2 py-1">Tanggal Permintaan</th>
                                    <th class="border px-2 py-1">Nomor SIMRS</th>
                                    <th class="border px-2 py-1">Nomor Laboratorium</th>
                                    <th class="border px-2 py-1">Nomor RM</th>
                                    <th class="border px-2 py-1">Nama Pasien</th>
                                    <th class="border px-2 py-1">Ruangan</th>
                                    <th class="border px-2 py-1">Dokter Pengirim</th>
                                    <th class="border px-2 py-1">Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
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
<script src="https://cdn.tailwindcss.com/"></script>


<script>
    document.addEventListener("DOMContentLoaded", function() {
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
                    console.log(d.rm_number);
                    d.ruangan = $("#ruangan").val();
                    d.start_date = $("#start_date").val();
                    d.end_date = $("#end_date").val();
                }
            },
            columns: [{
                    data: 'oh_trx_dt',
                    name: 'oh_trx_dt'
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
                    searchable: true
                },
                {
                    data: 'oh_last_name',
                    name: 'oh_last_name',
                    searchable: true
                },
                {
                    data: 'clinic_desc',
                    name: 'clinic_desc'
                },
                {
                    data: 'oh_dname',
                    name: 'oh_dname'
                },
                {
                    data: 'oh_ord_status',
                    name: 'oh_ord_status'
                }
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
            info: true,
            deferLoading: 0,
        });

        $("#search-button").on("click", function() {
            let button = $(this);
            let icon = $("#search-icon");
            let text = $("#search-text");
            let overlay = $("#loading-overlay");

            // Tampilkan overlay hanya di tabel
            overlay.removeClass("hidden").fadeIn(200);

            // Tampilkan efek loading pada tombol
            icon.removeClass("fa-search").addClass("fa-spinner fa-spin");
            text.text("Mencari...");
            button.prop("disabled", true).addClass("opacity-50 cursor-not-allowed");

            // Panggil ulang DataTable dengan parameter baru
            table.ajax.reload(null, false);

            // Setelah data selesai dimuat, sembunyikan efek loading
            setTimeout(() => {
                icon.removeClass("fa-spinner fa-spin").addClass("fa-search");
                text.text("Cari Hasil Pemeriksaan");
                button.prop("disabled", false).removeClass("opacity-50 cursor-not-allowed");

                // Hilangkan overlay loading
                overlay.fadeOut(200);
            }, 1500);
        });
    });
</script>