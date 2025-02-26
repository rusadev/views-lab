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

            <div class="bg-white shadow-sm rounded-lg mt-3 p-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">TAT Berdasarkan Kelompok Pemeriksaan</h3>
                <p class="text-sm text-gray-500 mb-4"> Grafik ini menunjukkan rata-rata waktu penyelesaian (Turnaround Time/TAT) untuk setiap kelompok dan jenis pemeriksaan dalam periode yang dipilih.. </p>
                <table class="w-full text-sm text-left border rounded-lg shadow-md">
                    <thead class="text-gray-700 bg-gray-200">
                        <tr>
                            <th class="px-4 py-2 border">Kelompok Pemeriksaan</th>
                            <th class="px-4 py-2 border">Total Tes</th>
                            <th class="px-4 py-2 border">Rata-rata TAT</th>
                        </tr>
                    </thead>
                    <tbody id="tat-group-table-body"></tbody>
                </table>
                <canvas id="tatGroupChart" class="mt-4"></canvas>
            </div>

            <div class="bg-white shadow-sm rounded-lg mt-3 p-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">TAT Berdasarkan Jenis Pemeriksaan</h3>
                <p class="text-sm text-gray-500 mb-4"> Tabel dibawah ini menampilkan rata-rata waktu penyelesaian (Turnaround Time/TAT) berdasarkan jenis pemeriksaan dalam periode yang dipilih. </p>
                <table class="w-full text-sm text-left border rounded-lg shadow-md">
                    <thead class="text-gray-700 bg-gray-200">
                        <tr>
                            <th class="px-4 py-2 border">Nama Pemeriksaan</th>
                            <th class="px-4 py-2 border">Kelompok Pemeriksaan</th>
                            <th class="px-4 py-2 border">Total Tes</th>
                            <th class="px-4 py-2 border">Rata-rata TAT</th>
                        </tr>
                    </thead>
                    <tbody id="tat-test-table-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

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

                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;


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
                    const response = await fetch(`/laboratorium/laporan/tat/data?start_date=${startDate}&end_date=${endDate}`);
                    const data = await response.json();

                    const updateTable = (elementId, records, columns) => {
                        const tbody = document.getElementById(elementId);
                        tbody.innerHTML = records.map(record => `
                        <tr class="bg-white border-b hover:bg-gray-100 text-gray-700">
                            ${columns.map(col => `<td class="px-4 py-2 border">${record[col]}</td>`).join('')}
                        </tr>
                    `).join('');
                    };

                    updateTable('tat-group-table-body', data.averageTATByGroup, ['test_group_name', 'total_tests', 'avg_tat_time']);
                    updateTable('tat-test-table-body', data.averageTATByTest, ['test_name', 'test_group_name', 'total_tests', 'avg_tat_time']);

                    if (window.tatGroupChartInstance) window.tatGroupChartInstance.destroy();
                    const ctx = document.getElementById('tatGroupChart').getContext('2d');

                    window.tatGroupChartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.averageTATByGroup.map(item => item.test_group_name),
                            datasets: [{
                                label: 'Rata-rata TAT (menit)',
                                data: data.averageTATByGroup.map(item => item.avg_tat_minutes),
                                backgroundColor: [
                                    'rgba(75, 192, 192)',
                                    'rgba(255, 99, 132)',
                                    'rgba(54, 162, 235)',
                                    'rgba(255, 165, 0)',
                                    'rgba(153, 102, 255)',
                                ],
                                borderColor: [
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 165, 0, 1)',
                                    'rgba(153, 102, 255, 1)',
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                datalabels: {
                                    anchor: 'end',
                                    align: 'end',
                                    color: 'black',
                                    font: {
                                        weight: 'bold'
                                    }
                                }
                            }
                        },
                        plugins: [ChartDataLabels] // Tambahkan plugin untuk data label
                    });



                } catch (error) {
                    console.error('Error fetching data:', error);
                    alert('Gagal mengambil data. Coba lagi.');
                } finally {
                    searchButton.disabled = false;
                    searchButton.innerHTML = `
                        <i class="fas fa-search"></i>
                        <span>Generate Laporan</span>
                    `;
                }
            });
        });
    </script>

</x-app-layout>