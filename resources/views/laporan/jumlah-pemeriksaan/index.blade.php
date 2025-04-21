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

            <!-- <div id="summary-section" class="mt-3"></div> -->
            <div id="test-group-section" class="mt-3"></div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const startDateInput = document.getElementById("start_date");
            const endDateInput = document.getElementById("end_date");
            const searchButton = document.getElementById("search-button");
            const BASE_URL = "{{ config('app.url') }}";
            const testGroupSection = document.getElementById("test-group-section");
            const summarySection = document.getElementById("summary-section");

            // Set default date to today
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

                const url = `${BASE_URL}/laboratorium/laporan/jumlah-pemeriksaan/data?start_date=${startDate}&end_date=${endDate}`;
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

            function renderData({ months, table, group_totals }) {
                // summarySection.innerHTML = renderSummaryTable(months, group_totals);
                testGroupSection.innerHTML = renderGroupedTables(months, table);
            }

            function renderSummaryTable(months, groupTotals) {
                if (!Array.isArray(groupTotals) || groupTotals.length === 0) {
                    return `<p class="text-sm text-gray-500 italic mt-2">Tidak ada data rekapitulasi kelompok pemeriksaan.</p>`;
                }

                return `
                    <div class="bg-white shadow-lg rounded-lg p-6 overflow-x-auto">
                        <h3 class="font-bold text-xl text-gray-700 mb-2">📊 Rekapitulasi Pemeriksaan</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Tabel berikut menampilkan jumlah pemeriksaan berdasarkan kelompok pemeriksaan dalam periode yang dipilih.
                        </p>
                        <table class="w-full text-left border-collapse border border-gray-300 min-w-max text-sm">
                            <thead class="bg-gray-200 text-gray-700 font-semibold">
                                <tr>
                                    <th class="border p-3">Kelompok Pemeriksaan</th>
                                    ${months.map(m => `<th class="border p-3 text-center">${m}</th>`).join('')}
                                    <th class="border p-3 text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${groupTotals.map(group => `
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-3 font-medium capitalize">${group.name || '-'}</td>
                                        ${months.map(m => `<td class="border p-3 text-center">${group.totals?.[m] ?? 0}</td>`).join('')}
                                        <td class="border p-3 text-center font-bold bg-indigo-50">${group.totals?.total ?? 0}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            function renderGroupedTables(months, rawatData) {
                if (!rawatData || Object.keys(rawatData).length === 0) {
                    return `<p class="text-sm text-gray-500 italic mt-2">Tidak ada data detail pemeriksaan per jenis rawat.</p>`;
                }

                return Object.entries(rawatData).map(([jenisRawat, rawatContent]) => {
                    const groupEntries = rawatContent.groups ? Object.entries(rawatContent.groups) : [];

                    // Hitung total pemeriksaan untuk setiap grup per bulan
                    const groupTotals = groupEntries.map(([groupName, groupData]) => {
                        return { 
                            groupName, 
                            totals: groupData.totals || {} 
                        };
                    });

                    return `
                        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
                            <h3 class="font-bold text-xl text-indigo-700 mb-1">Jumlah Pemeriksaan - ${jenisRawat}</h3>

                            <!-- Tabel Rekapitulasi Per Grup -->
                            <h4 class="font-semibold text-lg text-gray-700 mb-3">📊 Rekapitulasi Per Grup Pemeriksaan</h4>
                            <table class="w-full text-left border-collapse border border-gray-300 min-w-max text-sm">
                                <thead class="bg-gray-100 text-gray-700">
                                    <tr>
                                        <th class="border p-3">Nama Grup</th>
                                        ${months.map(m => `<th class="border p-3 text-center">${m}</th>`).join('')}
                                        <th class="border p-3 text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${groupTotals.map(group => `
                                        <tr class="hover:bg-gray-50">
                                            <td class="border p-3" width="30%">${group.groupName}</td>
                                            ${months.map(m => {
                                                const monthlyTotal = group.totals[m] ?? 0;
                                                return `<td class="border p-3 text-center">${monthlyTotal}</td>`;
                                            }).join('')}
                                            <td class="border p-3 text-center font-bold bg-gray-50">${group.totals.total ?? 0}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            <br>

                            <h4 class="font-semibold text-lg text-indigo-700 mb-1">📊 Detail Jumlah Pemeriksaan Pada ${jenisRawat}</h4>

                            <!-- Detail per Grup -->
                            ${groupEntries.map(([groupName, groupData]) => {
                                const totalGroup = Object.values(groupData.totals || {}).reduce((sum, value) => sum + value, 0);

                                return `
                                    <div class="mb-6">
                                        <h4 class="font-semibold text-md text-gray-600 mb-2">${groupName} <span class="text-sm text-gray-400">(Total: ${totalGroup})</span></h4>
                                        <table class="w-full text-left border-collapse border border-gray-300 min-w-max text-sm">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="border p-3" width="30%">Nama Pemeriksaan</th>
                                                    ${months.map(m => `<th class="border p-3 text-center">${m}</th>`).join('')}
                                                    <th class="border p-3 text-center">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${
                                                    groupData.tests
                                                        ? Object.entries(groupData.tests).map(([testName, testData]) => `
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="border p-3">${testName}</td>
                                                                ${months.map(m => `<td class="border p-3 text-center">${testData.totals?.[m] ?? 0}</td>`).join('')}
                                                                <td class="border p-3 text-center font-bold bg-gray-50">${testData.totals?.total ?? 0}</td>
                                                            </tr>
                                                        `).join('')
                                                        : `<tr><td colspan="${months.length + 2}" class="border p-3 italic text-center">Tidak ada data pemeriksaan</td></tr>`
                                                }
                                            </tbody>
                                        </table>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    `;
                }).join('');
            }
        });
    </script>


</x-app-layout>