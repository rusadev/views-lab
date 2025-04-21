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

            <!-- CITO Section -->
            <div class="bg-white shadow-sm rounded-lg mt-5 p-4">
                <div id="cito-ranap">
                    <h2 class="text-xl font-bold text-red-600 mb-4">TAT - Laporan CITO Rawat Inap</h2>
                    <!-- Chart for Cito by Group -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Chart Berdasarkan Kelompok Pemeriksaan (CITO Rawat Inap)</h3>
                        <canvas id="citoRanapChart"></canvas>
                    </div>

                    <!-- TAT by Group Table -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">TAT Berdasarkan Kelompok Pemeriksaan (CITO Rawat Inap)</h3>
                        <table class="w-full text-sm text-left border rounded-lg shadow-md">
                            <thead class="text-gray-700 bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 border">Kelompok Pemeriksaan</th>
                                    <th class="px-4 py-2 border">Total Tes</th>
                                    <th class="px-4 py-2 border">Rata-rata TAT</th>
                                </tr>
                            </thead>
                            <tbody id="cito-ranap-group-table-body"></tbody>
                        </table>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">TAT Berdasarkan Test Pemeriksaan (CITO Rawat Inap)</h3>
                        <table class="w-full text-sm text-left border rounded-lg shadow-md">
                            <thead class="text-gray-700 bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 border">Nama Pemeriksaan</th>
                                    <th class="px-4 py-2 border">Total Tes</th>
                                    <th class="px-4 py-2 border">Rata-rata TAT</th>
                                </tr>
                            </thead>
                            <tbody id="cito-ranap-test-table-body"></tbody>
                        </table>
                    </div>
                </div>

                <div id="cito-rajal">
                    <h2 class="text-xl font-bold text-red-600 mb-4">TAT - Laporan CITO Rawat Jalan</h2>
                    <!-- Chart for Cito by Group -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Chart Berdasarkan Kelompok Pemeriksaan (CITO Rawat Jalan)</h3>
                        <canvas id="citoRajalChart"></canvas>
                    </div>

                    <!-- TAT by Group Table -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">TAT Berdasarkan Kelompok Pemeriksaan (CITO Rawat Jalan)</h3>
                        <table class="w-full text-sm text-left border rounded-lg shadow-md">
                            <thead class="text-gray-700 bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 border">Kelompok Pemeriksaan</th>
                                    <th class="px-4 py-2 border">Total Tes</th>
                                    <th class="px-4 py-2 border">Rata-rata TAT</th>
                                </tr>
                            </thead>
                            <tbody id="cito-rajal-group-table-body"></tbody>
                        </table>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">TAT Berdasarkan Test Pemeriksaan (CITO Rawat Jalan)</h3>
                        <table class="w-full text-sm text-left border rounded-lg shadow-md">
                            <thead class="text-gray-700 bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 border">Nama Pemeriksaan</th>
                                    <th class="px-4 py-2 border">Total Tes</th>
                                    <th class="px-4 py-2 border">Rata-rata TAT</th>
                                </tr>
                            </thead>
                            <tbody id="cito-rajal-test-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- NON-CITO Section -->
            <div class="bg-white shadow-sm rounded-lg mt-5 p-4">
                <div id="non-cito-ranap">
                    <h2 class="text-xl font-bold text-indigo-600 mb-4">TAT - Laporan Non-CITO Rawat Inap</h2>

                    <!-- Chart for Non-Cito by Group -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Chart Berdasarkan Kelompok Pemeriksaan (Non-Cito)</h3>
                        <canvas id="noncitoRanapChart"></canvas>
                    </div>

                    <!-- TAT by Group Table -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">TAT Berdasarkan Kelompok Pemeriksaan (Non-Cito) - Rawat Inap</h3>
                        <table class="w-full text-sm text-left border rounded-lg shadow-md">
                            <thead class="text-gray-700 bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 border">Kelompok Pemeriksaan</th>
                                    <th class="px-4 py-2 border">Total Tes</th>
                                    <th class="px-4 py-2 border">Rata-rata TAT</th>
                                </tr>
                            </thead>
                            <tbody id="noncito-ranap-group-table-body"></tbody>
                        </table>
                    </div>

                    <!-- TAT by test Table -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">TAT Berdasarkan Test Pemeriksaan (Non-Cito) - Rawat Inap</h3>
                        <table class="w-full text-sm text-left border rounded-lg shadow-md">
                            <thead class="text-gray-700 bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 border">Nama Pemeriksaan</th>
                                    <th class="px-4 py-2 border">Total Tes</th>
                                    <th class="px-4 py-2 border">Rata-rata TAT</th>
                                </tr>
                            </thead>
                            <tbody id="noncito-ranap-test-table-body"></tbody>
                        </table>
                    </div>
                </div>

                <div id="non-cito-rajal">
                    <h2 class="text-xl font-bold text-indigo-600 mb-4">TAT - Laporan Non-CITO Rawat Jalan</h2>

                    <!-- Chart for Non-Cito by Group -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Chart Berdasarkan Kelompok Pemeriksaan (Non-CITO Rawat Jalan)</h3>
                        <canvas id="noncitoRajalChart"></canvas>
                    </div>

                    <!-- TAT by Group Table -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">TAT Berdasarkan Kelompok Pemeriksaan (Non-Cito) - Rawat Jalan</h3>
                        <table class="w-full text-sm text-left border rounded-lg shadow-md">
                            <thead class="text-gray-700 bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 border">Kelompok Pemeriksaan</th>
                                    <th class="px-4 py-2 border">Total Tes</th>
                                    <th class="px-4 py-2 border">Rata-rata TAT</th>
                                </tr>
                            </thead>
                            <tbody id="noncito-rajal-group-table-body"></tbody>
                        </table>
                    </div>

                    <!-- TAT by test Table -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">TAT Berdasarkan Test Pemeriksaan (Non-CITO Rawat Jalan)</h3>
                        <table class="w-full text-sm text-left border rounded-lg shadow-md">
                            <thead class="text-gray-700 bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 border">Nama Pemeriksaan</th>
                                    <th class="px-4 py-2 border">Total Tes</th>
                                    <th class="px-4 py-2 border">Rata-rata TAT</th>
                                </tr>
                            </thead>
                            <tbody id="noncito-rajal-test-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const startDateInput = document.getElementById("start_date");
            const endDateInput = document.getElementById("end_date");
            const searchButton = document.getElementById("search-button");

            const today = new Date().toISOString().split("T")[0];
            startDateInput.value = today;
            endDateInput.value = today;

            const BASE_URL = "{{ config('app.url') }}";

            let citoRanapChartInstance = null;
            let citoRajalChartInstance = null;
            let noncitoRanapChartInstance = null;
            let noncitoRajalChartInstance = null;


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
                    const response = await fetch(`${BASE_URL}/laboratorium/laporan/tat/data?start_date=${startDate}&end_date=${endDate}`);
                    const json = await response.json();

                    // Cito Data for Chart
                    const citoRanapData = json.cito.rawat_inap.by_group.map(item => ({
                        label: item.test_group_name,
                        data: item.total_tests,
                    }));

                    const citoRajalData = json.cito.rawat_jalan.by_group.map(item => ({
                        label: item.test_group_name,
                        data: item.total_tests,
                    }));

                    // Non-Cito Data for Chart
                    const nonCitoRanapData = json.non_cito.rawat_inap.by_group.map(item => ({
                        label: item.test_group_name,
                        data: item.total_tests,
                    }));

                    const nonCitoRajalData = json.non_cito.rawat_jalan.by_group.map(item => ({
                        label: item.test_group_name,
                        data: item.total_tests,
                    }));

                    // Fungsi Helper Buat Buat Chart
                    function renderBarChart(canvasId, chartInstance, labels, data, labelText, color) {
                        const ctx = document.getElementById(canvasId);
                        ctx.height = 150;
                        if (chartInstance) chartInstance.destroy();
                        return new Chart(ctx, {
                            type: "bar",
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: labelText,
                                    data: data,
                                    backgroundColor: color,
                                }],
                            },
                        });
                    }

                    // Update Charts
                    const citoColor = "#f87171";
                    const nonCitoColor = "#60a5fa";

                    // Cito Ranap
                    const citoRanapLabels = citoRanapData.map(item => item.label);
                    const citoRanapDataValues = citoRanapData.map(item => item.data);
                    citoRanapChartInstance = renderBarChart("citoRanapChart", citoRanapChartInstance, citoRanapLabels, citoRanapDataValues, "Total Tes Cito", citoColor);

                    // Cito Rajal
                    const citoRajalLabels = citoRajalData.map(item => item.label);
                    const citoRajalDataValues = citoRajalData.map(item => item.data);
                    citoRajalChartInstance = renderBarChart("citoRajalChart", citoRajalChartInstance, citoRajalLabels, citoRajalDataValues, "Total Tes Cito", citoColor);

                    // Non-Cito Ranap
                    const nonCitoRanapLabels = nonCitoRanapData.map(item => item.label);
                    const nonCitoRanapDataValues = nonCitoRanapData.map(item => item.data);
                    noncitoRanapChartInstance = renderBarChart("noncitoRanapChart", noncitoRanapChartInstance, nonCitoRanapLabels, nonCitoRanapDataValues, "Total Tes Non-Cito", nonCitoColor);

                    // Non-Cito Rajal
                    const nonCitoRajalLabels = nonCitoRajalData.map(item => item.label);
                    const nonCitoRajalDataValues = nonCitoRajalData.map(item => item.data);
                    noncitoRajalChartInstance = renderBarChart("noncitoRajalChart", noncitoRajalChartInstance, nonCitoRajalLabels, nonCitoRajalDataValues, "Total Tes Non-Cito", nonCitoColor);


                    // Update Cito Group Table
                    const groupCitoRanapTable = document.getElementById("cito-ranap-group-table-body");
                    groupCitoRanapTable.innerHTML = json.cito.rawat_inap.by_group.map(item => `
                        <tr>
                            <td class="px-4 py-2 border">${item.test_group_name}</td>
                            <td class="px-4 py-2 border">${item.total_tests}</td>
                            <td class="px-4 py-2 border">${item.avg_tat_time}</td>
                        </tr>
                    `).join('');

                    const groupCitoRajalTable = document.getElementById("cito-rajal-group-table-body");
                    groupCitoRajalTable.innerHTML = json.cito.rawat_jalan.by_group.map(item => `
                        <tr>
                            <td class="px-4 py-2 border">${item.test_group_name}</td>
                            <td class="px-4 py-2 border">${item.total_tests}</td>
                            <td class="px-4 py-2 border">${item.avg_tat_time}</td>
                        </tr>
                    `).join('');

                    // Update Non-Cito Group Table
                    const nonCitoRanapGroupTable = document.getElementById("noncito-ranap-group-table-body");
                    nonCitoRanapGroupTable.innerHTML = json.non_cito.rawat_inap.by_group.map(item => `
                        <tr>
                            <td class="px-4 py-2 border">${item.test_group_name}</td>
                            <td class="px-4 py-2 border">${item.total_tests}</td>
                            <td class="px-4 py-2 border">${item.avg_tat_time}</td>
                        </tr>
                    `).join('');

                    const nonCitoRajalGroupTable = document.getElementById("noncito-rajal-group-table-body");
                    nonCitoRajalGroupTable.innerHTML = json.non_cito.rawat_jalan.by_group.map(item => `
                        <tr>
                            <td class="px-4 py-2 border">${item.test_group_name}</td>
                            <td class="px-4 py-2 border">${item.total_tests}</td>
                            <td class="px-4 py-2 border">${item.avg_tat_time}</td>
                        </tr>
                    `).join('');



                    // Update Cito Test Table
                    const citoTestRanapTable = document.getElementById("cito-ranap-test-table-body");
                    citoTestRanapTable.innerHTML = json.cito.rawat_inap.by_test.map(item => `
                        <tr>
                            <td class="px-4 py-2 border">${item.test_name}</td>
                            <td class="px-4 py-2 border">${item.total_tests}</td>
                            <td class="px-4 py-2 border">${item.avg_tat_time}</td>
                        </tr>
                    `).join('');

                    const citoTestRajalTable = document.getElementById("cito-rajal-test-table-body");
                    citoTestRajalTable.innerHTML = json.cito.rawat_jalan.by_test.map(item => `
                        <tr>
                            <td class="px-4 py-2 border">${item.test_name}</td>
                            <td class="px-4 py-2 border">${item.total_tests}</td>
                            <td class="px-4 py-2 border">${item.avg_tat_time}</td>
                        </tr>
                    `).join('');



                    // Update Non-Cito Group Table
                    const nonCitoTestRanapTable = document.getElementById("noncito-ranap-test-table-body");
                    nonCitoTestRanapTable.innerHTML = json.non_cito.rawat_inap.by_test.map(item => `
                        <tr>
                            <td class="px-4 py-2 border">${item.test_name}</td>
                            <td class="px-4 py-2 border">${item.total_tests}</td>
                            <td class="px-4 py-2 border">${item.avg_tat_time}</td>
                        </tr>
                    `).join('');

                    const nonCitoTestRajalTable = document.getElementById("noncito-rajal-test-table-body");
                    nonCitoTestRajalTable.innerHTML = json.non_cito.rawat_jalan.by_test.map(item => `
                        <tr>
                            <td class="px-4 py-2 border">${item.test_name}</td>
                            <td class="px-4 py-2 border">${item.total_tests}</td>
                            <td class="px-4 py-2 border">${item.avg_tat_time}</td>
                        </tr>
                    `).join('');

                    searchButton.disabled = false;
                    searchButton.innerHTML = `<i class="fas fa-search"></i> Generate Laporan`;

                } catch (error) {
                    console.error("Error fetching data:", error);
                    searchButton.disabled = false;
                    searchButton.innerHTML = `<i class="fas fa-search"></i> Generate Laporan`;
                }
            });
        });
    </script>
</x-app-layout>
