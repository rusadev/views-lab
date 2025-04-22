<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-400 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <i class="fas fa-microscope text-white"></i>
            {{ __('Laporan Jumlah Pemeriksaan') }}
        </h2>
    </x-slot>
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4 text-gray-900">
                    <form method="GET" action="#" class="space-y-4">
                        <div class="flex flex-wrap gap-4 items-end">
                            <!-- Tanggal Awal & Akhir -->
                            <div id="date_range_section" class="flex w-1/4 gap-2">
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

                        <div>
                            <button id="search-button" class="bg-gradient-to-r from-indigo-600 to-indigo-400 hover:from-indigo-600 hover:to-indigo-800 text-white text-sm font-semibold px-3 py-2 rounded flex items-center gap-2 shadow-lg transition-all duration-300">
                                <i class="fas fa-search"></i>
                                <span>Generate Laporan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg mt-3">
                <div class="p-4 text-gray-900">
                    <!-- Container tombol export DataTables dan search -->
                    <div id="export-container" class="flex justify-between items-center mb-4 flex-wrap gap-2"></div>
                    <div id="test-group-section"></div>
                </div>
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
    document.addEventListener("DOMContentLoaded", function () {
        const startDateInput = document.getElementById("start_date");
        const endDateInput = document.getElementById("end_date");
        const searchButton = document.getElementById("search-button");
        const BASE_URL = "{{ config('app.url') }}";
        const testGroupSection = document.getElementById("test-group-section");

        const today = new Date().toISOString().split("T")[0];
        startDateInput.value = today;
        endDateInput.value = today;

        searchButton.addEventListener("click", async (e) => {
            e.preventDefault();
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

                if (startDate > endDate) {
                    alert("Tanggal mulai tidak boleh lebih besar dari tanggal akhir!");
                    return;
                }

                const url = `/laboratorium/laporan/jumlah-pemeriksaan/data?start_date=${startDate}&end_date=${endDate}`;
                toggleLoading(true);

                try {
                    const response = await fetch(url);
                    const result = await response.json();

                    const months = result.months || [];
                    const table = result.table || {};
                    const group_totals = result.group_totals || [];

                    renderData({ months, table, group_totals });
                } catch (error) {
                    console.error("Error fetching data:", error);
                    alert("Terjadi kesalahan saat mengambil data. Silakan coba lagi.");
                } finally {
                    toggleLoading(false);
                }
            });

            function toggleLoading(loading) {
                searchButton.disabled = loading;
                searchButton.innerHTML = loading
                    ? `<i class="fas fa-spinner fa-spin"></i><span>Memuat...</span>`
                    : `<i class="fas fa-search"></i><span>Generate Laporan</span>`;
            }

            const url = `${BASE_URL}/laboratorium/laporan/jumlah-pemeriksaan/data?start_date=${startDate}&end_date=${endDate}`;
            toggleLoading(true);

            try {
                const response = await fetch(url);
                const result = await response.json();

                const months = result.months || [];
                const pivotData = result.pivot || [];

                testGroupSection.innerHTML = "";

                let tableHtml = `
                    <div class="overflow-x-auto mt-4">
                        <table id="pivotTable" class="table-auto w-full border-collapse border border-gray-300 text-sm">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 border text-center" rowspan="2">Nama Pemeriksaan</th>`;

                months.forEach(month => {
                    tableHtml += ` 
                        <th class="px-4 py-2 border text-center" colspan="3">${month}</th>
                    `;
                });

                tableHtml += `
                    <th class="px-4 py-2 border text-center" colspan="3">Total</th>
                </tr><tr>`;

                months.forEach(() => {
                    tableHtml += `
                        <th class="px-4 py-2 border text-center">Rawat Inap</th>
                        <th class="px-4 py-2 border text-center">Rawat Jalan</th>
                        <th class="px-4 py-2 border text-center">Lainnya</th>
                    `;
                });

                tableHtml += `
                    <th class="px-4 py-2 border text-center">Rawat Inap</th>
                    <th class="px-4 py-2 border text-center">Rawat Jalan</th>
                    <th class="px-4 py-2 border text-center">Lainnya</th>
                </tr></thead><tbody>`;

                pivotData.forEach(item => {
                    let row = `<tr><td class="px-4 py-2 border text-center">${item.test_name}</td>`;

                    let totalRawatInap = 0;
                    let totalRawatJalan = 0;
                    let totalLainnya = 0;

                    months.forEach(month => {
                        const rawatInap = item.data[month]?.["Rawat Inap"] || 0;
                        const rawatJalan = item.data[month]?.["Rawat Jalan"] || 0;
                        const lainnya = item.data[month]?.["Lainnya"] || 0;

                        totalRawatInap += rawatInap;
                        totalRawatJalan += rawatJalan;
                        totalLainnya += lainnya;

                        row += `
                            <td class="px-4 py-2 border text-center">${rawatInap}</td>
                            <td class="px-4 py-2 border text-center">${rawatJalan}</td>
                            <td class="px-4 py-2 border text-center">${lainnya}</td>
                        `;
                    });

                    row += `
                        <td class="px-4 py-2 border text-center">${totalRawatInap}</td>
                        <td class="px-4 py-2 border text-center">${totalRawatJalan}</td>
                        <td class="px-4 py-2 border text-center">${totalLainnya}</td>
                    </tr>`;

                    tableHtml += row;
                });

                tableHtml += `</tbody></table></div>`;
                testGroupSection.innerHTML = tableHtml;

                setTimeout(() => {
                    // Cek apakah DataTable sudah ada, jika iya, destroy dulu
                    if ($.fn.DataTable.isDataTable('#pivotTable')) {
                        $('#pivotTable').DataTable().destroy();
                    }

                    $('#pivotTable').DataTable({
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: 'Download Excel',
                            exportOptions: {
                                orthogonal: 'export',
                                columns: ':visible',
                                modifier: {
                                    // Pastikan semua baris header ikut
                                    page: 'all'
                                }
                            },
                            customize: function (xlsx) {
                                var sheet = xlsx.xl.worksheets['sheet1.xml'];
                                var rows = $('row', sheet);

                                // Buat style bold untuk dua baris pertama (thead)
                                rows.each(function (i) {
                                    if (i === 0 || i === 1) {
                                        $(this).attr('customHeight', '1');
                                        $('c', this).attr('s', '51'); // Gaya bold default Excel
                                    }
                                });

                                // Tambahkan style kustom (opsional)
                                var styles = xlsx.xl['styles.xml'];
                                var newStyle = `<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>`;
                                $(styles).find('cellXfs').append(newStyle);
                            }
                        },
                        {
                            extend: 'pdfHtml5',
                            text: 'Download PDF',
                            exportOptions: {
                                orthogonal: 'export',
                                columns: ':visible',
                                modifier: {
                                    page: 'all'
                                }
                            }
                        }],
                        searching: true,
                        paging: false,
                        ordering: false,
                        scrollX: true,
                        initComplete: function () {
                            const exportButtons = $(".dt-buttons");
                            const searchBox = $(".dt-search");

                            // Bersihkan isi sebelumnya
                            $("#export-container").empty();

                            // Masukkan elemen dengan layout Flexbox
                            $("#export-container")
                                .append(searchBox)
                                .append(exportButtons);

                            // Tambahkan styling tambahan jika perlu
                            exportButtons.addClass("mb-0");
                            searchBox.addClass("mb-0");
                        }
                    });
                }, 100);


            } catch (error) {
                console.error("Error fetching data:", error);
                alert("Terjadi kesalahan saat mengambil data. Silakan coba lagi.");
            } finally {
                toggleLoading(false);
            }
        });

        function toggleLoading(loading) {
            searchButton.disabled = loading;
            searchButton.innerHTML = loading
                ? `<i class="fas fa-spinner fa-spin"></i><span> Memuat...</span>`
                : `<i class="fas fa-search"></i><span> Generate Laporan</span>`;
        }
    });
</script>




