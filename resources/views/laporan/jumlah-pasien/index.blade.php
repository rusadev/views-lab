<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-400 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <svg class="w-6 h-6 text-white animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h6m4 12H5a2 2 0 01-2-2V5a2 2 0 012-2h9l5 5v12a2 2 0 01-2 2z" />
            </svg>
            {{ __('Laporan Jumlah Pasien') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4 text-gray-900">
                    <form method="GET" action="#" class="space-y-4">
                        <div class="flex flex-wrap gap-4 items-end">
                            <div id="date_range_section" class="flex w-1/4 gap-2">
                                <div class="w-1/2">
                                    <label for="start_date" class="block text-sm font-medium mb-1">Tanggal Awal</label>
                                    <input type="date" id="start_date" class="w-full p-2 border rounded text-sm">
                                </div>
                                <div class="w-1/2">
                                    <label for="end_date" class="block text-sm font-medium mb-1">Tanggal Akhir</label>
                                    <input type="date" id="end_date" class="w-full p-2 border rounded text-sm">
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

            <div class="bg-white shadow-sm rounded-lg mt-4">
                <div class="p-4 text-gray-900">

                    <h3 class="font-bold text-lg text-grey-600 mb-2">Detail Laporan Jumlah Kunjungan Pasien</h3>
                    <table id="laporanTable" class="min-w-full border border-grey-200 table-auto text-sm font-sans text-center">
                        <thead class="bg-grey-100 text-grey-700">
                            <tr id="tableHeader">
                                <th class="px-4 py-2 border bg-grey-200">Tipe Pasien</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                        </tbody>
                    </table>

                    <div class="mt-6">
                        <h4 class="font-bold text-grey-600">Pie Chart Total Keseluruhan (Rawat Jalan, Rawat Inap, dll)</h4>
                        <canvas id="totalPieChart" class="max-w-md mx-auto h-64"></canvas>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script>
        Chart.register(ChartDataLabels);
        document.addEventListener("DOMContentLoaded", function() {
            fetch("{{ route('laporan.jumlah-pasien.data') }}")
                .then(response => {
                    if (!response.ok) {
                        throw new Error("HTTP error " + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    let monthSet = new Set();
                    let totalData = {
                        labels: [],
                        totals: [],
                        categories: {}
                    };

                    for (let key in data) {
                        const row = data[key];
                        if (key !== "Total") {
                            for (let col in row) {
                                if (col !== "total") {
                                    monthSet.add(col);
                                }
                            }
                        }
                    }

                    let months = Array.from(monthSet);
                    months.sort();

                    // 2. Bangun header tabel:
                    const headerRow = document.getElementById("tableHeader");
                    while (headerRow.children.length > 1) {
                        headerRow.removeChild(headerRow.lastChild);
                    }
                    months.forEach(month => {
                        const th = document.createElement("th");
                        th.textContent = month;
                        th.className = "px-4 py-2 border bg-grey-200 text-grey-700 ";
                        headerRow.appendChild(th);
                    });
                    const thTotal = document.createElement("th");
                    thTotal.textContent = "Total";
                    thTotal.className = "px-4 py-2 border bg-grey-200 text-grey-700";
                    headerRow.appendChild(thTotal);

                    // 3. Bangun body tabel
                    const tbody = document.getElementById("tableBody");
                    tbody.innerHTML = "";

                    let rowsOrder = [];
                    let totalRow = null;
                    for (let key in data) {
                        if (key === "Total") {
                            totalRow = { key: key, row: data[key] };
                        } else {
                            rowsOrder.push({ key: key, row: data[key] });
                        }
                    }

                    rowsOrder.forEach(item => {
                        const tr = document.createElement("tr");
                        const tdName = document.createElement("td");
                        tdName.textContent = item.key;
                        tdName.className = "px-4 py-2 border bg-grey-50 text-grey-700";
                        tr.appendChild(tdName);

                        months.forEach(month => {
                            const td = document.createElement("td");
                            td.textContent = item.row[month] || 0;
                            td.className = "px-4 py-2 border bg-grey-50 text-grey-700";
                            tr.appendChild(td);
                        });

                        const tdRowTotal = document.createElement("td");
                        tdRowTotal.textContent = item.row["total"] || 0;
                        tdRowTotal.className = "px-4 py-2 border font-bold bg-grey-100 text-grey-700";
                        tr.appendChild(tdRowTotal);

                        tbody.appendChild(tr);

                        // Update totalData for categories
                        totalData.categories[item.key] = item.row["total"] || 0;
                    });

                    if (totalRow) {
                        const tr = document.createElement("tr");
                        const tdLabel = document.createElement("td");
                        tdLabel.textContent = totalRow.key;
                        tdLabel.className = "px-4 py-2 border font-bold bg-grey-200 text-grey-700";
                        tr.appendChild(tdLabel);

                        months.forEach(month => {
                            const td = document.createElement("td");
                            td.textContent = totalRow.row[month] || 0;
                            td.className = "px-4 py-2 border font-bold bg-grey-200 text-grey-700";
                            tr.appendChild(td);
                        });

                        const tdGrandTotal = document.createElement("td");
                        tdGrandTotal.textContent = totalRow.row["total"] || 0;
                        tdGrandTotal.className = "px-4 py-2 border font-bold bg-grey-200 text-grey-700";
                        tr.appendChild(tdGrandTotal);

                        tbody.appendChild(tr);

                        // Prepare data for Pie chart categories
                        totalData.labels = Object.keys(totalData.categories);
                        totalData.totals = Object.values(totalData.categories);
                    }

                    // Render Pie chart for total
                    const ctx = document.getElementById('totalPieChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: totalData.labels,
                            datasets: [{
                                data: totalData.totals,
                                backgroundColor: ['#60a5fa', '#3b82f6', '#1e40af', '#1e3a8a'],
                                borderColor: '#ffffff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: false,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
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
                                    formatter: function(value, context) {
                                        var total = context.dataset.data.reduce(function(sum, val) {
                                            return sum + val;
                                        }, 0);
                                        var percentage = ((value / total) * 100).toFixed(2) + '%';
                                        return percentage + '\n' + `${value} Orang`;
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
                                    offset: 5,
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error("Terjadi error: ", error);
                });
        });
    </script>

</x-app-layout>
