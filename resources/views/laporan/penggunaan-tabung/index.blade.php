<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-400 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <i class="fas fa-vial text-white"></i>
            {{ __('Laporan Penggunaan Tabung') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4 text-gray-900">
                    <form id="report-form" class="space-y-4">
                        <div class="flex flex-wrap gap-4 items-end">
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
            <!-- Tabel Laporan -->
            <div class="bg-white shadow-sm rounded-lg mt-4">
                <div class="p-4 text-gray-900">
                    <h3 class="font-bold text-lg text-gray-600 mb-2">
                        Laporan Penggunaan Tabung
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Tabel berikut menyajikan data penggunaan tabung berdasarkan periode yang dipilih.
                        Laporan ini membantu dalam pemantauan stok dan efisiensi penggunaan tabung dalam layanan kesehatan.
                    </p>
                    <table id="tabungTable" class="table-auto w-full border-collapse border border-gray-300 mt-4 text-sm">
                        <thead id="tableHeadTabung"></thead>
                        <tbody id="tableBodyTabung"></tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg mt-4">
                <div class="p-4 text-gray-900">
                    <h3 class="font-bold text-lg text-gray-600 mb-2">Grafik Penggunaan Tabung</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Grafik ini menunjukkan jumlah penggunaan tabung berdasarkan periode yang dipilih.
                    </p>
                    <canvas id="tabungChart" class="max-h-[350px]"></canvas>
                </div>
            </div>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let tabungChart;

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
                    const response = await fetch(`/laboratorium/laporan/penggunaan-tabung/data?start_date=${startDate}&end_date=${endDate}`);
                    const {
                        data,
                        samples
                    } = await response.json();

                    if (!data.length) {
                        renderTable([], []);
                        renderChart([], []);
                        return;
                    }

                    renderTable(data, samples);
                    renderChart(data, samples);
                } catch (error) {
                    console.error('Gagal mengambil data:', error);
                    alert('Terjadi kesalahan saat mengambil data.');
                } finally {
                    searchButton.disabled = false;
                    searchButton.innerHTML = `
                        <i class="fas fa-search"></i>
                        <span>Generate Laporan</span>
                    `;
                }
            });

            function renderTable(data, samples) {
                const tableHead = document.getElementById('tableHeadTabung');
                const tableBody = document.getElementById('tableBodyTabung');

                tableHead.innerHTML = '';
                tableBody.innerHTML = '';

                if (data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="100%" class="text-center p-4 text-gray-500">Tidak ada data</td></tr>`;
                    return;
                }

                let headerHTML = `<tr class="bg-gray-100"><th class="px-4 py-2 text-left">Tanggal</th>`;
                headerHTML += samples.map(sample => `<th class="px-4 py-2">${sample}</th>`).join('');
                headerHTML += `</tr>`;
                tableHead.innerHTML = headerHTML;

                tableBody.innerHTML = data.map(row => `
            <tr class="hover:bg-gray-100">
                <td class="px-4 py-2">${row.date}</td>
                ${samples.map(sample => `<td class="px-4 py-2 text-center">${row[sample] || 0}</td>`).join('')}
            </tr>
        `).join('');
            }

            function renderChart(data, samples) {
                const labels = data.map(row => row.date);
                const colorMap = {
                    'Arteri': 'rgba(255, 69, 0)',
                    'C. Tubuh': 'rgba(0, 191, 255)',
                    'Cairan Pleura': 'rgba(135, 206, 235)',
                    'EDTA': 'rgba(128, 0, 128)',
                    'Faeces': 'rgba(139, 69, 19)',
                    'SS Tulang': 'rgba(169, 169, 169)',
                    'Serum': 'rgba(255, 0, 0)',
                    'Serum(2J)': 'rgba(220, 20, 60)',
                    'Sitrat': 'rgba(0, 128, 255)',
                    'Urin': 'rgba(255, 223, 0)'
                };

                const datasets = samples.map(sample => ({
                    label: sample,
                    data: data.map(row => row[sample] || 0),
                    backgroundColor: colorMap[sample] || 'rgba(100, 100, 100, 0.7)', // Default abu-abu jika tidak dikenal
                    borderWidth: 1
                }));

                const ctx = document.getElementById('tabungChart').getContext('2d');

                if (tabungChart) {
                    tabungChart.destroy();
                }

                tabungChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
    </script>



</x-app-layout>