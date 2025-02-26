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
                                <i id="search-icon" class="fas fa-search"></i>
                                <span id="search-text">Generate Laporan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="summary-section" class="mt-3"></div>
            <div id="test-group-section" class="mt-3"></div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const startDateInput = document.getElementById("start_date");
            const endDateInput = document.getElementById("end_date");
            const searchButton = document.getElementById("search-button");

            const today = new Date().toISOString().split("T")[0];
            startDateInput.value = today;
            endDateInput.value = today;

            const BASE_URL = "{{ config('app.url') }}";
            const testGroupSection = document.getElementById("test-group-section");
            const summarySection = document.getElementById("summary-section");

            searchButton.addEventListener("click", async (e) => {
                e.preventDefault();

                const startDate = document.getElementById("start_date").value;
                const endDate = document.getElementById("end_date").value;
                const url = `/laboratorium/laporan/jumlah-pemeriksaan/data?start_date=${startDate}&end_date=${endDate}`;

                if (startDate > endDate) {
                    alert("Tanggal mulai tidak boleh lebih besar dari tanggal akhir!");
                    return;
                }

                searchButton.disabled = true;
                searchButton.innerHTML = `
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Memuat...</span>
                `;

                try {
                    const response = await fetch(url);
                    const data = await response.json();
                    displayData(data);
                } catch (error) {
                    console.error("Error fetching data:", error);
                    alert("Terjadi kesalahan saat mengambil data. Silakan coba lagi.");


                } finally {
                    searchButton.disabled = false;
                    searchButton.innerHTML = `
                        <i class="fas fa-search"></i>
                        <span>Generate Laporan</span>
                    `;
                }
            });

            function displayData({
                months,
                table,
                grand_total,
                group_totals
            }) {
                testGroupSection.innerHTML = createTestGroupTable(months, table);
                summarySection.innerHTML = createSummaryTable(months, group_totals);
            }

            function createTestGroupTable(months, tableData) {
                let tableHtml = `
        <div class="bg-white shadow-lg rounded-lg p-6 overflow-x-auto">
            <h3 class="font-bold text-lg text-gray-600 mb-2">Rekapitulasi Pemeriksaan per Nama Pemeriksaan</h3>
            <p class="text-sm text-gray-500 mb-4">Tabel berikut menampilkan jumlah pemeriksaan berdasarkan nama pemeriksaan yang telah dilakukan.</p>
            <table class="w-full text-left border-collapse border border-gray-300 min-w-max text-sm">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="border p-3">Kelompok Pemeriksaan</th>
                        <th class="border p-3">Nama Pemeriksaan</th>
                        ${months.map(month => `<th class="border p-3 text-center">${month}</th>`).join('')}
                        <th class="border p-3 text-center">Total</th>
                    </tr>
                </thead>
                <tbody>`;

                Object.entries(tableData).forEach(([group, groupData]) => {
                    const groupTests = Object.entries(groupData.tests);

                    groupTests.forEach(([test, testData], index) => {
                        tableHtml += `<tr class="hover:bg-gray-50">`;
                        if (index === 0) {
                            tableHtml += `<td class="border p-3 font-semibold capitalize bg-gray-100" rowspan="${groupTests.length}">${group}</td>`;
                        }
                        tableHtml += `
                    <td class="border p-3">${test}</td>
                    ${months.map(month => `<td class="border p-3 text-center">${testData.totals[month] || 0}</td>`).join('')}
                    <td class="border p-3 text-center font-bold">${testData.totals.total || 0}</td>
                </tr>`;
                    });
                });

                tableHtml += `</tbody></table></div>`;
                return tableHtml;
            }

            function createSummaryTable(months, groupTotals) {
                return `
        <div class="bg-white shadow-lg rounded-lg p-6 overflow-x-auto">
            <h3 class="font-bold text-lg text-gray-600 mb-2">Rekapitulasi Pemeriksaan per Kelompok Pemeriksaan</h3>
            <p class="text-sm text-gray-500 mb-4">Tabel berikut menampilkan jumlah pemeriksaan berdasarkan Kelompok pemeriksaan yang telah dilakukan.</p>
            <table class="w-full text-left border-collapse border border-gray-300 min-w-max">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="border p-3">Kelompok Pemeriksaan</th>
                        ${months.map(month => `<th class="border p-3 text-center">${month}</th>`).join('')}
                        <th class="border p-3 text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${groupTotals.map(group => `
                    <tr class="hover:bg-gray-50">
                        <td class="border p-3 font-semibold capitalize">${group.name}</td>
                        ${months.map(month => `<td class="border p-3 text-center">${group.totals[month] || 0}</td>`).join('')}
                        <td class="border p-3 text-center font-bold">${group.totals.total || 0}</td>
                    </tr>`).join('')}
                </tbody>
            </table>
        </div>`;
            }
        });
    </script>
</x-app-layout>