<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-400 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <i class="fas fa-hourglass-half text-white"></i>
            {{ __('Laporan TAT') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4 text-gray-900">
                    <form id="report-form" class="space-y-4">
                        <div class="flex flex-wrap gap-4 items-end">
                            <div class="flex w-1/4 gap-2">
                                <div class="w-1/2">
                                    <label for="start_date" class="block text-sm font-medium mb-1">Tanggal Awal</label>
                                    <input type="date" id="start_date" name="start_date" class="w-full p-2 border rounded text-sm">
                                </div>
                                <div class="w-1/2">
                                    <label for="end_date" class="block text-sm font-medium mb-1">Tanggal Akhir</label>
                                    <input type="date" id="end_date" name="end_date" class="w-full p-2 border rounded text-sm">
                                </div>
                            </div>
                        </div>

                        <button id="search-button" class="bg-gradient-to-r from-indigo-600 to-indigo-400 hover:from-indigo-600 hover:to-indigo-800 text-white text-sm font-semibold px-3 py-2 rounded flex items-center gap-2 shadow-lg transition-all duration-300">
                            <i id="search-icon" class="fas fa-search"></i>
                            <span id="search-text">Generate Laporan</span>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Report Section -->
            <div class="bg-white shadow-sm rounded-lg mt-5 p-4">
               
                <h2 class="text-lg font-bold mb-2">TAT CITO - Berdasarkan Nama Pemeriksaan</h2>
                <div id="export-container-cito"></div>
                <table id="table-cito-test" class="w-full table-auto border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-center">
                            <th class="border p-2" rowspan="2">Kode</th>
                            <th class="border p-2" rowspan="2">Nama Test</th>
                            <th class="border p-2 text-center" colspan="2">Rawat Jalan</th>
                            <th class="border p-2 text-center" colspan="2">Rawat Inap</th>
                        </tr>
                        <tr class="bg-gray-100 text-center">
                            <th class="border p-2 text-center">TAT</th>
                            <th class="border p-2 text-center">Total</th>
                            <th class="border p-2 text-center">TAT</th>
                            <th class="border p-2 text-center">Total</th>
                        </tr>
                    </thead>

                    <tbody></tbody>
                </table>
            </div>

            <div class="bg-white shadow-sm rounded-lg mt-5 p-4">
                <h2 class="text-lg font-bold mb-2">TAT NON-CITO - Berdasarkan Nama Pemeriksaan    </h2>
                <div id="export-container-noncito"></div>
                <table id="table-noncito-test" class="w-full table-auto border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-center">
                            <th class="border p-2" rowspan="2">Kode</th>
                            <th class="border p-2" rowspan="2">Nama Test</th>
                            <th class="border p-2 text-center" colspan="2">Rawat Jalan</th>
                            <th class="border p-2 text-center" colspan="2">Rawat Inap</th>
                        </tr>
                        <tr class="bg-gray-100 text-center">
                            <th class="border p-2 text-center">TAT</th>
                            <th class="border p-2 text-center">Total</th>
                            <th class="border p-2 text-center">TAT</th>
                            <th class="border p-2 text-center">Total</th>
                        </tr>
                    </thead>

                    <tbody></tbody>
                </table>
           </div>      
        </div>
    </div>
</x-app-layout>
<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.tailwindcss.js"></script>

<!-- DataTables Buttons JS -->
<script src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>
<!-- JSZip for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<!-- DataTables Buttons extensions (to enable CSV, Excel, PDF, etc.) -->
<script src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.html5.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const startDateInput = document.getElementById("start_date");
        const endDateInput = document.getElementById("end_date");
        const searchButton = document.getElementById("search-button");

        const today = new Date().toISOString().split("T")[0];
        startDateInput.value = today;
        endDateInput.value = today;

        const BASE_URL = "{{ config('app.url') }}";

        document.getElementById('report-form').addEventListener('submit', async function(event) {
            event.preventDefault();

            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            if (startDate > endDate) {
                alert("Tanggal mulai tidak boleh lebih besar dari tanggal akhir!");
                return;
            }

            searchButton.disabled = true;
            searchButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Memuat...`;

            try {
                const response = await fetch(`/laboratorium/laporan/tat/data?start_date=${startDate}&end_date=${endDate}`);
                const json = await response.json();

                const populateTable = (selector, data) => {
                    const $table = $(selector);
                    const tbody = $table.find("tbody");
                    tbody.empty(); // Kosongkan isi tbody

                    // Destroy DataTable jika sudah ada
                    if ($.fn.DataTable.isDataTable($table)) {
                        $table.DataTable().clear().destroy();
                    }

                    data.forEach(item => {
                        const rawatJalan = item.rawat_jalan ?? { tat_formatted: "-", total_tests: 0 };
                        const rawatInap = item.rawat_inap ?? { tat_formatted: "-", total_tests: 0 };

                        const row = `
                            <tr>
                                <td class="border p-2">${item.code}</td>
                                <td class="border p-2">${item.name}</td>
                                <td class="border p-2 text-center">${rawatJalan.tat_formatted}</td>
                                <td class="border p-2 text-center">${rawatJalan.total_tests}</td>
                                <td class="border p-2 text-center">${rawatInap.tat_formatted}</td>
                                <td class="border p-2 text-center">${rawatInap.total_tests}</td>
                            </tr>
                        `;
                        tbody.append(row);
                    });

                    $table.DataTable({
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: 'Download Excel',
                            exportOptions: {
                                orthogonal: 'export',
                                columns: ':visible',
                                modifier: { page: 'all' }
                            },
                            filename: function () {
                                if (selector === "#table-cito-test") return 'Laporan TAT Cito';
                                if (selector === "#table-noncito-test") return 'Laporan TAT Non-Cito';
                                return 'Laporan TAT';
                            },
                            customize: function (xlsx) {
                                var sheet = xlsx.xl.worksheets['sheet1.xml'];
                                var rows = $('row', sheet);

                                rows.each(function (i) {
                                    if (i === 0 || i === 1) {
                                        $(this).attr('customHeight', '1');
                                        $('c', this).attr('s', '51');
                                    }
                                });

                                var styles = xlsx.xl['styles.xml'];
                                var newStyle = `<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>`;
                                $(styles).find('cellXfs').append(newStyle);
                            }
                        }],
                        searching: true,
                        paging: false,
                        ordering: false,
                        scrollX: true,
                        initComplete: function (settings, json) {
                            const api = new $.fn.dataTable.Api(settings);
                            const tableContainer = $(api.table().container());

                            const exportButtons = tableContainer.find(".dt-buttons");
                            const searchBox = tableContainer.find(".dataTables_filter");

                            let containerId = "";
                            if (selector === "#table-cito-test") {
                                containerId = "#export-container-cito";
                            } else if (selector === "#table-noncito-test") {
                                containerId = "#export-container-noncito";
                            }

                            const flexContainer = $(`
                                <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                                    <div class="search-area w-full md:w-auto"></div>
                                    <div class="button-area w-full md:w-auto flex justify-end"></div>
                                </div>
                            `);

                            flexContainer.find(".search-area").append(searchBox);
                            flexContainer.find(".button-area").append(exportButtons);

                            $(containerId).empty().append(flexContainer);
                            exportButtons.addClass("mb-0");
                            searchBox.addClass("mb-0");
                        }
                    });
                };

                populateTable('#table-cito-test', json.cito.by_test);
                populateTable('#table-noncito-test', json.non_cito.by_test);

            } catch (error) {
                console.error("Error fetching data:", error);
                alert("Gagal mengambil data laporan!");
            }

            searchButton.disabled = false;
            searchButton.innerHTML = `<i class="fas fa-search"></i> Generate Laporan`;
        });
    });
</script>
