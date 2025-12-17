<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-400 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <i class="fas fa-user-injured text-white"></i>
            {{ __('Laporan Jumlah Pasien') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4 text-gray-900">
                    <form id="report-form" method="GET" action="#" class="space-y-4">
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

                        <div class="flex gap-4">
                            <button id="search-button" type="button" class="bg-gradient-to-r from-indigo-600 to-indigo-400 hover:from-indigo-600 hover:to-indigo-800 text-white text-sm font-semibold px-3 py-2 rounded flex items-center gap-2 shadow-lg transition-all duration-300">
                                <i id="search-icon" class="fas fa-search"></i>
                                <span id="search-text">Generate Laporan</span>
                            </button>
                            <button id="export-word-button" type="button" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-3 py-2 rounded flex items-center gap-2 shadow-lg transition-all duration-300">
                                <i class="fas fa-file-word"></i>
                                <span>Export ke Word</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg mt-4">
                <div class="p-4 text-gray-900">
                    <h3 class="font-bold text-lg text-gray-600 mb-2">
                        Rekapitulasi Kunjungan Pasien per Jenis Pelayanan
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Tabel berikut menampilkan jumlah kunjungan pasien berdasarkan jenis layanan,
                        termasuk <strong>Rawat Inap</strong>, <strong>Rawat Jalan</strong>, dan <strong>Layanan Lainnya</strong>.
                    </p>
                    <table id="laporanTable" class="min-w-full border border-gray-200 table-auto text-sm font-sans text-center">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr id="tableHeader"></tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg mt-4">
                <div class="text-center"></div>
                <div class="p-4 text-gray-900">
                    <h3 class="font-bold text-lg text-gray-600 mb-2">Distribusi Pasien Berdasarkan Tipe Layanan</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Grafik ini menunjukkan proporsi pasien berdasarkan jenis layanan yang diterima, yaitu <strong>Rawat Inap</strong> dan <strong>Rawat Jalan</strong>.
                    </p>
                    <div class="w-full justify-center">
                        <canvas id="totalPieChart" class="max-h-[350px]"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
                    <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col items-center">
                        <h3 class="font-bold text-lg text-gray-600 mb-2">Distribusi Pasien Berdasarkan Gender</h3>
                        <p class="text-sm text-gray-500 mb-4 text-center">
                            Grafik ini menampilkan perbandingan jumlah pasien berdasarkan <strong>jenis kelamin</strong>.
                        </p>
                        <div class="w-full flex justify-center">
                            <canvas id="genderPieChart" class="max-w-[350px] max-h-[350px]"></canvas>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col items-center">
                        <h3 class="font-bold text-lg text-gray-600 mb-2">Distribusi Pasien Berdasarkan Kelompok Usia</h3>
                        <p class="text-sm text-gray-500 mb-4 text-center">
                            Grafik ini menggambarkan jumlah pasien dalam berbagai <strong>kelompok usia</strong>.
                        </p>
                        <div class="w-full">
                            <canvas id="ageBarChart" class="max-h-[350px]"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg mt-4">
                <div class="p-4 text-gray-900">
                    <h3 class="font-bold text-lg text-gray-600 mb-2">
                        Rekapitulasi Kunjungan Pasien per Ruangan
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Tabel berikut menyajikan jumlah kunjungan pasien berdasarkan ruangan pelayanan,
                        memberikan gambaran distribusi pasien di berbagai unit layanan.
                    </p>
                    <table id="distribusiTable" class="table-auto w-full border-collapse border border-gray-300 mt-4 text-sm">
                        <thead id="tableHeadRuangan"></thead>
                        <tbody id="tableBodyRuangan"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script>
        Chart.register(ChartDataLabels);

        let distribusiChart = null;
        let genderChart = null;
        let ageChart = null;

        document.addEventListener("DOMContentLoaded", function () {
            const startDateInput = document.getElementById("start_date");
            const endDateInput = document.getElementById("end_date");
            const searchButton = document.getElementById("search-button");
            const exportWordButton = document.getElementById("export-word-button"); // Get the new export button

            // Set default date to today's date
            const today = new Date();
            const todayFormatted = today.toISOString().split("T")[0]; // YYYY-MM-DD
            startDateInput.value = todayFormatted;
            endDateInput.value = todayFormatted;

            fetchData(); // Initial data fetch on page load

            async function fetchData() {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;

                if (startDate > endDate) {
                    alert("Tanggal mulai tidak boleh lebih besar dari tanggal akhir!");
                    return;
                }

                // Disable buttons and show loading state
                searchButton.disabled = true;
                searchButton.innerHTML = `
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Memuat...</span>
                `;
                exportWordButton.disabled = true; // Disable export button too

                try {
                    console.log(`Mengambil data dari ${startDate} hingga ${endDate}...`);

                    await fetchDataAndRenderKunjunganPasien(startDate, endDate);
                    await fetchDataDistribusiPerRuangan(startDate, endDate);

                    console.log("Data berhasil diperoleh.");
                } catch (error) {
                    console.error("Terjadi kesalahan saat mengambil data:", error);
                    alert("Terjadi kesalahan saat mengambil data. Silakan coba lagi.");
                } finally {
                    // Enable buttons and restore text
                    searchButton.disabled = false;
                    searchButton.innerHTML = `
                        <i class="fas fa-search"></i>
                        <span>Generate Laporan</span>
                    `;
                    exportWordButton.disabled = false; // Enable export button
                }
            }

            searchButton.addEventListener("click", fetchData);

            // Add event listener for the export button
            exportWordButton.addEventListener("click", function() {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;
                window.location.href = `{{ route('laporan.jumlah-pasien.export-word') }}?start_date=${startDate}&end_date=${endDate}`;
            });
        });


        async function fetchDataAndRenderKunjunganPasien(startDate, endDate) {
            // No need for BASE_URL if using relative path
            try {
                const response = await fetch(`/laboratorium/laporan/jumlah-pasien/data?start_date=${startDate}&end_date=${endDate}`);
                if (!response.ok) throw new Error("HTTP error " + response.status);

                const res = await response.json();
                const dataDistribusiRuangan = res.distribusi_ruangan['Tipe Ruangan'];
                const dataDistribusiGender = res.distribusi_ruangan['Gender'];
                const dataDistribusiUsia = res.distribusi_ruangan['Usia'];

                renderRekapitulasiKunjunganPasienTable(dataDistribusiRuangan);
                renderDistribusiChart(dataDistribusiRuangan);

                renderGenderChart(dataDistribusiGender);
                renderAgeChart(dataDistribusiUsia);
            } catch (error) {
                console.error("Terjadi error: ", error);
            }
        }

        async function fetchDataDistribusiPerRuangan(startDate, endDate) {
            // No need for BASE_URL if using relative path
            try {
                const response = await fetch(`/laboratorium/laporan/jumlah-pasien/data?start_date=${startDate}&end_date=${endDate}`);
                if (!response.ok) {
                    throw new Error("Gagal mengambil data");
                }

                const res = await response.json();
                const result = res.getDistribusiPerRuangan;
                // console.log(result); // Keep for debugging if needed
                updateTableDistribusi(result.data, result.months);
            } catch (error) {
                console.error("Terjadi error: ", error);
            }
        }


        function renderRekapitulasiKunjunganPasienTable(data) {
            let monthSet = new Set();
            let totalData = {
                labels: [],
                totals: [],
                categories: {}
            };

            for (let key in data) {
                if (key !== "Total Per Bulan") {
                    Object.keys(data[key]).forEach(col => col !== "Total" && monthSet.add(col)); // Exclude 'Total' from months
                }
            }

            let months = Array.from(monthSet).sort();
            updateTableHeader(months);
            updateTableBody(data, months, totalData);
        }


        function updateTableHeader(months) {
            const headerRow = document.getElementById("tableHeader");
            headerRow.innerHTML = "<th class='px-4 py-2 border bg-gray-100 text-gray-700'>Jenis Pelayanan</th>"; // Changed to bg-gray-100

            months.forEach(month => {
                headerRow.innerHTML += `<th class='px-4 py-2 border bg-gray-100 text-gray-700'>${month}</th>`; // Changed to bg-gray-100
            });
            headerRow.innerHTML += `<th class='px-4 py-2 border bg-gray-100 text-gray-700'>Total</th>`; // Added Total column header
        }

        function updateTableBody(data, months, totalData) {
            const tbody = document.getElementById("tableBody");
            tbody.innerHTML = "";

            // Filter out 'Total Per Bulan' from the main data for rendering rows
            let rows = Object.entries(data).filter(([key]) => key !== "Total Per Bulan");

            rows.forEach(([key, row]) => {
                tbody.innerHTML += createRow(key, row, months, false);
                totalData.categories[key] = row["Total"] || 0; // Use 'Total' for categories total
            });

            if (data["Total Per Bulan"]) { // Changed to "Total Per Bulan"
                tbody.innerHTML += createRow("Total", data["Total Per Bulan"], months, true); // Changed to "Total Per Bulan"
                totalData.labels = Object.keys(totalData.categories);
                totalData.totals = Object.values(totalData.categories);
            }
        }


        function updateTableDistribusi(data, months) {
            const tableHead = document.getElementById('tableHeadRuangan');
            const tableBody = document.getElementById('tableBodyRuangan');
            tableHead.innerHTML = '';
            tableBody.innerHTML = '';

            // Create Table Header
            let theadRow = `<tr>
                <th class="border px-4 py-2 bg-gray-100">Tipe Ruangan</th>
                <th class="border px-4 py-2 bg-gray-100">Nama Ruangan</th>`;

            months.forEach(month => {
                theadRow += `<th class="border px-4 py-2 bg-gray-100">${month}</th>`;
            });

            theadRow += `<th class="border px-4 py-2 bg-gray-100">Total</th></tr>`;
            tableHead.innerHTML = theadRow;

            // Populate Table Data
            Object.keys(data).forEach(tipe => {
                const ruanganKeys = Object.keys(data[tipe]).filter(ruangan => ruangan !== "Total");
                let firstRow = true;

                ruanganKeys.forEach(ruangan => {
                    let row = `<tr>`;
                    if (firstRow) {
                        row += `<td class="border px-4 py-2 align-middle text-sm" rowspan="${ruanganKeys.length}">${tipe}</td>`;
                        firstRow = false;
                    }

                    row += `<td class="border px-4 py-2">${ruangan}</td>`;

                    months.forEach(month => {
                        let jumlahPasien = data[tipe][ruangan][month] || 0;
                        row += `<td class="border px-4 py-2 text-center text-sm">${jumlahPasien}</td>`;
                    });

                    let totalPerRuangan = data[tipe][ruangan]["Total"] || 0;
                    row += `<td class="border px-4 py-2 text-center font-bold text-sm">${totalPerRuangan}</td></tr>`;

                    tableBody.innerHTML += row;
                });

                // Total row for each room type
                let totalRow = `<tr class="bg-gray-200 font-bold text-center">
                    <td class="border px-4 py-2 text-sm" colspan="2">Total ${tipe}</td>`;

                months.forEach(month => {
                    let totalPerBulan = data[tipe]["Total"][month] || 0;
                    totalRow += `<td class="border px-4 py-2 text-sm">${totalPerBulan}</td>`;
                });

                let totalKeseluruhan = data[tipe]["Total"]["Total"] || 0;
                totalRow += `<td class="border px-4 py-2 text-sm">${totalKeseluruhan}</td></tr>`;

                tableBody.innerHTML += totalRow;
            });
        }

        function createRow(label, rowData, months, isTotal) {
            let rowClass = isTotal ? "bg-gray-200 font-bold" : "bg-white"; // Changed to bg-gray-200 and bg-white
            let row = `<tr><td class='px-4 py-2 border text-gray-700 ${rowClass}'>${label}</td>`;

            months.forEach(month => {
                row += `<td class='px-4 py-2 border text-gray-700 ${rowClass}'>${rowData[month] || 0}</td>`;
            });
            row += `<td class='px-4 py-2 border text-gray-700 ${rowClass}'>${rowData['Total'] || 0}</td>`; // Added Total column data

            return row;
        }

        function renderDistribusiChart(data) {
            let labels = Object.keys(data).filter(key => key !== "Total Per Bulan");
            let totals = labels.map(label => data[label]['Total'] || 0);
            const ctx = document.getElementById('totalPieChart').getContext('2d');

            if (distribusiChart !== null) {
                distribusiChart.destroy();
            }

            distribusiChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: totals,
                        backgroundColor: ['#60a5fa', '#3b82f6', '#1e40af', '#1e3a8a'],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'rect',
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 13
                                },
                                padding: 10
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1e3a8a',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderWidth: 1,
                            borderColor: '#3b82f6'
                        },
                        datalabels: {
                            formatter: (value, context) => {
                                let total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                                return `${((value / total) * 100).toFixed(2)}%\n${value} Orang`;
                            },
                            color: '#ffffff',
                            font: {
                                family: 'Inter, sans-serif',
                                weight: 'bold',
                                size: 11
                            },
                            align: 'center',
                            anchor: 'center',
                            padding: 6,
                            offset: 5
                        }
                    }
                }
            });
        }

        function renderGenderChart(data) {
            let labels = Object.keys(data);
            let totals = labels.map(label => data[label] || 0);

            const ctx = document.getElementById('genderPieChart').getContext('2d');

            if (genderChart !== null) {
                genderChart.destroy();
            }

            genderChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: totals,
                        backgroundColor: [
                            '#FF5733',
                            '#3498DB',
                            '#2ECC71',
                            '#F39C12',
                            '#9B59B6'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    layout: {
                        padding: {
                            top: 20,
                            bottom: 20
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'rect',
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 14
                                },
                                padding: 12
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1e3a8a',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderWidth: 1,
                            borderColor: '#3b82f6'
                        },
                        datalabels: {
                            formatter: (value, context) => {
                                let total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                                let percentage = ((value / total) * 100).toFixed(1);
                                return `${percentage}%\n(${value} Orang)`;
                            },
                            color: '#ffffff',
                            font: {
                                family: 'Inter, sans-serif',
                                weight: 'bold',
                                size: 13
                            },
                            align: 'center',
                            anchor: 'center',
                            padding: 8,
                            offset: 6, // Distance for text to avoid overlap
                            clip: false // So it's not cut off
                        }
                    }
                },
                plugins: [ChartDataLabels] // Enable data labels on pie chart
            });
        }


        function renderAgeChart(data) {
            let labels = Object.keys(data);
            let totals = labels.map(label => data[label] || 0);

            const ctx = document.getElementById('ageBarChart').getContext('2d');

            if (ageChart !== null) {
                ageChart.destroy();
            }


            ageChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Pasien',
                        data: totals,
                        backgroundColor: ['#ff6b6b', '#ff9f43', '#feca57', '#48dbfb', '#1dd1a1'],
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        maxBarThickness: 50 // Adjust bar thickness
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true, // Helps prevent label clipping
                    layout: {
                        padding: {
                            top: 20,  // Add extra space at the top to prevent label clipping
                            bottom: 20 // For bottom X-axis labels
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#374151',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: '#e5e7eb'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#374151',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1e3a8a',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderWidth: 1,
                            borderColor: '#3b82f6'
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            clip: false, // Ensure labels are visible even outside chart area
                            color: '#374151',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            formatter: (value) => value.toLocaleString()
                        }
                    }
                },
                plugins: [ChartDataLabels] // Show patient count above bars
            });
        }
    </script>

</x-app-layout>