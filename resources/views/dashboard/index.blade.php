<x-app-layout>
    <div class="py-4 bg-white">
        <div class="mx-auto sm:px-6 lg:px-8">
            <!-- Tambahkan di HTML -->
            <div id="globalLoading" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; z-index: 1000; text-align: center;">
                <div style="animation: fadeIn 0.5s ease-in-out; display: flex; flex-direction: column; align-items: center;">
                    <div class="spinner" style="border: 4px solid rgba(255, 255, 255, 0.3); border-top: 4px solid white; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin-bottom: 20px;"></div>
                    <div style="font-weight: bold; font-size: 20px;">Loading, please wait...</div>
                </div>
            </div>

            <div class="grid grid-cols-3 items-center mb-3 text-xs">

                <div class="col-span-1 text-sm font-semibold text-left text-blue-800">
                    <small id="countdownDisplay" class="text-xs">00:00</small>
                </div>
                <div class="col-span-1 text-3xl font-bold text-center bg-gradient-to-r from-blue-500 to-blue-800 bg-clip-text text-transparent">
                    Dashboard Laboratorium Patologi Klinik
                </div>

                <div class="col-span-1 flex justify-end items-center space-x-2">
                    <div class="flex flex-col">
                        <input
                            type="date"
                            id="startDate"
                            class="mt-1 h-6 w-18 text-base px-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" />
                    </div>
                    <div class="flex flex-col">
                        <input
                            type="date"
                            id="endDate"
                            class="mt-1 h-6 w-18 text-base px-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" />
                    </div>
                    <button
                        id="fetchDataButton"
                        class="h-6 px-6 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-md shadow-lg transform transition hover:scale-105 hover:from-blue-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <span class="font-medium">Show</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Left Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3 items-stretch text-center w-full">
                    <!-- Card dengan nuansa biru (gradasi baru) -->
                    <div class="p-4 rounded-2xl shadow-lg transition-all hover:shadow-xl transform hover:scale-105 duration-300 ease-in-out bg-gradient-to-r from-[#0D47A1] to-[#1976D2] w-full flex flex-col">
                        <h3 class="text-xl font-bold mb-2 text-white">Kunjungan Pasien</h3>
                        <p class="text-5xl font-bold text-white text-center" id="kunjunganPasien">...</p>
                    </div>
                    <!-- Permintaan - Warna Biru Gelap ke Biru Terang -->
                    <div class="p-4 rounded-2xl shadow-lg transition-all hover:shadow-xl transform hover:scale-105 duration-300 ease-in-out bg-gradient-to-r from-[#0D47A1] to-[#1976D2] w-full flex flex-col">
                        <h3 class="text-lg font-bold mb-2 text-white">Permintaan Pemeriksaan</h3>
                        <p class="text-5xl font-bold text-white" id="permintaanPemeriksaan">...</p>
                    </div>

                    <!-- Diselesaikan - Warna Biru Terang ke Biru Laut -->

                    <div class="p-4 rounded-2xl shadow-lg transition-all hover:shadow-xl transform hover:scale-105 duration-300 ease-in-out bg-gradient-to-r from-[#F57F17] to-[#FF9800] w-full flex flex-col">
                        <h3 class="text-lg font-bold mb-2 text-white">Menunggu</h3>
                        <p class="text-5xl font-bold text-white" id="pemeriksaanBelumDikerjakan">...</p>
                    </div>


                    <div class="p-4 rounded-2xl shadow-lg transition-all hover:shadow-xl transform hover:scale-105 duration-300 ease-in-out bg-gradient-to-r from-[#66BB6A] to-[#43A047] w-full flex flex-col">
                        <h3 class="text-lg font-bold mb-2 text-white">Diselesaikan</h3>
                        <p class="text-5xl font-bold text-white" id="pemeriksaanSelesai">...</p>
                    </div>




                    <!-- Card sesuai status dengan gradasi biru -->
                    <!-- <div class="p-4 rounded-2xl shadow-lg transition-all hover:shadow-xl transform hover:scale-105 duration-300 ease-in-out bg-gradient-to-r from-[#607D8B] to-[#455A64] w-full flex flex-col">
                        <h3 class="text-lg font-bold mb-2 text-white">Pemeriksaan</h3>
                        <p class="text-5xl font-bold text-white" id="pemeriksaanKeseluruhan">...</p>
                    </div> -->


                    <!-- Belum Dikerjakan - Warna Grey -->

                    

                    <!-- <div class="p-4 rounded-2xl shadow-lg transition-all hover:shadow-xl transform hover:scale-105 duration-300 ease-in-out bg-gradient-to-r from-[#FF7043] to-[#FF1744] w-full flex flex-col">
                        <h3 class="text-lg font-bold mb-2 text-white">Pending</h3>
                        <p class="text-5xl font-bold text-white" id="pemeriksaanPending">...</p>
                    </div> -->
                    

                    <!-- Sedang Diproses - Warna Kuning Cerah -->
                    <!-- <div class="p-4 rounded-2xl shadow-lg transition-all hover:shadow-xl transform hover:scale-105 duration-300 ease-in-out bg-gradient-to-r from-[#F57F17] to-[#FF9800] w-full flex flex-col">
                        <h3 class="text-lg font-bold mb-2 text-white">Diproses</h3>
                        <p class="text-5xl font-bold text-white" id="pemeriksaanSedangDiproses">...</p>
                    </div> -->


                    <!-- Selesai - Warna Hijau Cerah -->
                    <!-- <div class="p-4 rounded-2xl shadow-lg transition-all hover:shadow-xl transform hover:scale-105 duration-300 ease-in-out bg-gradient-to-r from-[#66BB6A] to-[#43A047] w-full flex flex-col">
                        <h3 class="text-lg font-bold mb-2 text-white">Validasi</h3>
                        <p class="text-5xl font-bold text-white" id="pemeriksaanSelesaiDikerjakan">...</p>
                    </div> -->
                </div>

                <!-- Center Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3 items-stretch text-center w-full">
                    <div class="p-4 rounded-2xl shadow-lg bg-white w-full flex flex-col">
                        <h3 class="text-lg font-bold mb-2 text-blue-800">Rawat Jalan</h3>
                        <canvas id="donutChartRawatJalan" style="max-width: 200px; margin: auto; width: 100%; height: 100%;"></canvas>
                        <div id="chartDataRawatJalan" class="flex justify-between items-center space-x-4 mt-4">
                            <div id="selesaiDataRajal" class="text-sm font-medium" style="color: rgba(37, 99, 235, 1)">Selesai: 0</div>
                            <div id="belumSelesaiDataRajal" class="text-sm font-medium" style="color: #D32F2F;">Belum Selesai: 0</div> <!-- Red color -->
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl shadow-lg bg-white w-full flex flex-col">
                        <h3 class="text-lg font-bold mb-2 text-blue-800">Rawat Inap</h3>
                        <canvas id="donutChartRawatInap" style="max-width: 200px; margin: auto; width: 100%; height: 100%;"></canvas>
                        <div id="chartDataRawatInap" class="flex justify-between items-center space-x-4 mt-4">
                            <div id="selesaiDataRanap" class="text-sm font-medium" style="color: rgba(37, 99, 235, 1)">Selesai: 0</div>
                            <div id="belumSelesaiDataRanap" class="text-sm font-medium" style="color: #D32F2F;">Belum Selesai: 0</div> <!-- Red color -->
                        </div>
                    </div>
                </div>

                <!-- Right Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-1 gap-3 items-stretch text-center w-full">
                    <div class="p-4 rounded-2xl shadow-lg bg-white w-full flex flex-col">
                        <h3 class="text-lg font-bold mb-2 text-blue-800">Turn Around Time (TAT)</h3>
                        <small class="text-xs font-bold mb-2 text-blue-800">(Rata Rata Menit)</small>
                        <canvas id="averageTATChart" style="width: 100%; height: 100%; margin: auto;"></canvas>
                    </div>
                </div>
            </div>
            <!-- Pie Charts Section -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mt-1 w-full py-2">

                <div class="bg-white p-2 rounded-xl shadow-md flex flex-col items-center w-full">
                    <span class="text-sm font-bold text-gray-700 w-full text-left flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2 animate-pulse-fast"></i> Monitoring Nilai Kritis
                    </span>

                    <div class="w-full mt-2 overflow-y-auto" style="max-height: 200px; overflow-y: auto;">
                        <table class="w-full border-collapse text-xs">
                            <thead style="position: sticky; top: 0; background-color: #f3f4f6; z-index: 10;">
                                <tr>
                                    <th class="p-2 text-left" style="padding: 8px; border-bottom: 1px solid #d1d5db;">No lab</th>
                                    <th class="p-2 text-left" style="padding: 8px; border-bottom: 1px solid #d1d5db;">Nama Pasien</th>
                                    <th class="p-2 text-left" style="padding: 8px; border-bottom: 1px solid #d1d5db;">Parameter</th>
                                    <th class="p-2 text-left" style="padding: 8px; border-bottom: 1px solid #d1d5db;">Nilai</th>
                                    <th class="p-2 text-left" style="padding: 8px; border-bottom: 1px solid #d1d5db;">Flag</th>
                                </tr>
                            </thead>
                            <tbody id="nilai-kritis"></tbody>
                        </table>
                    </div>

                </div>

                <div class="bg-white p-3 rounded-xl shadow-md flex flex-col items-center w-full">
                    <span class="text-sm font-bold text-gray-700 w-full text-left">Spesimen Yang Diterima</span>
                    <canvas id="distribusiSpesimenChart"></canvas>
                </div>
                <!-- Pie Chart 1: Distribusi Jenis Pemeriksaan -->
                <div class="bg-white p-3 rounded-xl shadow-md flex flex-col items-center w-full">
                    <span class="text-sm font-bold text-gray-700 w-full text-left">Distribusi Jenis Pemeriksaan</span>
                    <canvas id="distribusiPemeriksaanChart" style="width: 100%; height: 180px; margin: auto;"></canvas>
                </div>

                <!-- Pie Chart 2: Distribusi Kunjungan Pasien -->
                <div class="bg-white p-3 rounded-xl shadow-md flex flex-col items-center w-full">
                    <span class="text-sm font-bold text-gray-700 w-full text-left">Distribusi Kunjungan Pasien</span>
                    <canvas id="distribusiRanapRajalChart" style="width: 100%; height: 180px; margin: auto;"></canvas>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 w-full py-2">
                <!-- Pie Chart: Waktu Tunggu Pemeriksaan -->
                <div class="bg-white rounded-xl shadow-md flex flex-col items-center w-full h-72">
                    <span class="text-sm font-bold text-gray-700 w-full text-left px-3 pt-3">Permintaan Pemeriksaan Yang Diterima</span>
                    <div class="flex-grow w-full h-full px-4 py-3">
                        <canvas id="permintaanPemeriksaanChart" class="w-full h-48"></canvas>
                    </div>
                </div>
                <!-- Line Chart: Permintaan Berdasarkan Waktu -->
                <div class="bg-white rounded-xl shadow-md flex flex-col items-center w-full h-72">
                    <span class="text-sm font-bold text-gray-700 w-full text-left px-3 pt-3">Permintaan Berdasarkan Waktu</span>
                    <div class="flex-grow w-full h-full px-4 py-3">
                        <canvas id="permintaanPerWaktuChart" class="w-full h-48"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- kunjungan pasien  -->
    <script>
        Chart.register(ChartDataLabels);

        function fetchDashboardData() {

            let startDateInput = document.getElementById('startDate');
            let endDateInput = document.getElementById('endDate');

            let startDate = startDateInput.value;
            let endDate = endDateInput.value;

            const BASE_URL = "{{ config('app.url') }}";

            // const response = await fetch(`${BASE_URL}/laboratorium/laporan/tat/data?start_date=${startDate}&end_date=${endDate}`);



            // Pastikan tidak mengubah input tanggal user secara tidak sengaja
            console.log(`Fetching data with startDate: ${startDate} and endDate: ${endDate}`);
            if (startDate && endDate) {
                console.log([startDate, endDate]);
                $.ajax({
                    url: `${BASE_URL}/laboratorium/dashboard/data`,
                    type: "GET",
                    data: {
                        start_date: startDate,
                        end_date: endDate,
                    },
                    cache: false,
                    success: function(response) {
                        try {
                            // Data dari response
                            const kunjungan = response.kunjunganData || {};
                            const statusPemeriksaan = response.statusPemeriksaan || {};

                            const rawatJalanInap = response.permintaanRawatJalanInap || {};
                            const distribusiRajalRanap = response.distribusiKunjunganPasien || [];
                            const distribusiPemeriksaan = response.distribusiPemeriksaan || [];
                            const distribusiSpesimen = response.distribusiSpesimen || [];
                            const averageTAT = response.averageTAT || [];
                            const permintaanPemeriksaan = response.permintaanPemeriksaan || [];
                            const permintaanPerWaktu = response.permintaanPerWaktu || [];
                            const nilaiKritis = response.nilaiKritis || [];

                            renderTableNilaiKritis(nilaiKritis);

                            // Rawat Jalan 
                            const rawatJalanSelesai = rawatJalanInap?.rajal?.pemeriksaan_selesai || 0;
                            const rawatJalanBelum = rawatJalanInap?.rajal?.pemeriksaan_belum_selesai || 0;

                            // Update data di bawah chart jika elemen ada
                            $("#selesaiDataRajal").text(`Selesai: ${rawatJalanSelesai}`);
                            $("#belumSelesaiDataRajal").text(`Belum Selesai: ${rawatJalanBelum}`);

                            // Rawat Inap
                            const rawatInapSelesai = rawatJalanInap?.ranap?.pemeriksaan_selesai || 0;
                            const rawatInapBelum = rawatJalanInap?.ranap?.pemeriksaan_belum_selesai || 0;

                            $("#selesaiDataRanap").text(`Selesai: ${rawatInapSelesai}`);
                            $("#belumSelesaiDataRanap").text(`Belum Selesai: ${rawatInapBelum}`);

                            // Update teks di dashboard
                            $("#kunjunganPasien").text(kunjungan.kunjungan_pasien || 0);
                            $("#permintaanPemeriksaan").text(kunjungan.jumlah_permintaan || 0);
                            $("#pemeriksaanSelesai").text(kunjungan.pemeriksaan_selesai || 0);

                            $("#pemeriksaanKeseluruhan").text(statusPemeriksaan.total_pemeriksaan || 0);
                            $("#pemeriksaanSedangDiproses").text(statusPemeriksaan.total_diproses || 0);
                            $("#pemeriksaanBelumDikerjakan").text(statusPemeriksaan.total_belum_dikerjakan || 0);
                            $("#pemeriksaanSelesaiDikerjakan").text(statusPemeriksaan.total_selesai || 0);
                            $("#pemeriksaanPending").text(statusPemeriksaan.total_pending || 0);
                            // Update Chart Rawat Jalan & Inap
                            if (window.chartRawatJalan) {
                                chartRawatJalan.data.datasets[0].data = [rawatJalanSelesai, rawatJalanBelum];
                                chartRawatJalan.update();
                            }

                            if (window.chartRawatInap) {
                                chartRawatInap.data.datasets[0].data = [rawatInapSelesai, rawatInapBelum];
                                chartRawatInap.update();
                            }

                            // Update Chart Distribusi Pasien
                            const distribusiLabels = distribusiRajalRanap.map(item => item.jenis_rawat || "Unknown");
                            const distribusiData = distribusiRajalRanap.map(item => parseInt(item.jumlah_pasien) || 0);

                            if (window.chartDistribusiRanapRajal) {
                                chartDistribusiRanapRajal.data.labels = distribusiLabels;
                                chartDistribusiRanapRajal.data.datasets[0].data = distribusiData;
                                chartDistribusiRanapRajal.update();
                            }

                            // Update Chart Distribusi Pemeriksaan
                            const distribusiPemeriksaanLabels = distribusiPemeriksaan.map(item => item.test_group_name || "Unknown");
                            const distribusiPemeriksaanData = distribusiPemeriksaan.map(item => parseInt(item.total) || 0);

                            if (window.chartDistribusiPemeriksaan) {
                                chartDistribusiPemeriksaan.data.labels = distribusiPemeriksaanLabels;
                                chartDistribusiPemeriksaan.data.datasets[0].data = distribusiPemeriksaanData;
                                chartDistribusiPemeriksaan.update();
                            }

                            // Update Chart Distribusi Spesimen
                            const distribusiSpesimenLabels = distribusiSpesimen.map(item => item.sample || "Unknown");
                            const distribusiSpesimenData = distribusiSpesimen.map(item => parseInt(item.total) || 0);

                            if (window.chartDistribusiSpesimen) {
                                chartDistribusiSpesimen.data.labels = distribusiSpesimenLabels;
                                chartDistribusiSpesimen.data.datasets[0].data = distribusiSpesimenData;
                                chartDistribusiSpesimen.update();
                            }

                            const AverageTATLabels = averageTAT.map(item => item.test_group_name || "Unknown");
                            const AverageTATData = averageTAT.map(item => parseInt(item.avg_tat_minutes) || 0);

                            if (window.chartAverageTAT) {
                                chartAverageTAT.data.labels = AverageTATLabels;
                                chartAverageTAT.data.datasets[0].data = AverageTATData;
                                chartAverageTAT.update();
                            }


                            const permintaanPemeriksaanLabels = permintaanPemeriksaan.map(item => item.pemeriksaan || "Unknown");
                            const permintaanPemeriksaanDataSelesai = permintaanPemeriksaan.map(item => parseInt(item.pemeriksaan_selesai) || 0);
                            const permintaanPemeriksaanDataBelum = permintaanPemeriksaan.map(item => parseInt(item.pemeriksaan_belum_selesai) || 0);

                            if (window.chartPermintaanPemeriksaan) {
                                chartPermintaanPemeriksaan.data.labels = permintaanPemeriksaanLabels;
                                chartPermintaanPemeriksaan.data.datasets[0].data = permintaanPemeriksaanDataSelesai;
                                chartPermintaanPemeriksaan.data.datasets[1].data = permintaanPemeriksaanDataBelum;
                                chartPermintaanPemeriksaan.update();
                            }

                            const permintaanPerWaktuLabels = permintaanPerWaktu.map(item => item.hour || "?");
                            const permintaanPerWaktuTotal = permintaanPerWaktu.map(item => parseInt(item.total_keseluruhan) || 0);
                            const permintaanPerWaktuRajal = permintaanPerWaktu.map(item => parseInt(item.rajal) || 0);
                            const permintaanPerWaktuRanap = permintaanPerWaktu.map(item => parseInt(item.ranap) || 0);

                            if (window.chartPermintaanPerWaktu) {
                                chartPermintaanPerWaktu.data.labels = permintaanPerWaktuLabels;
                                chartPermintaanPerWaktu.data.datasets[0].data = permintaanPerWaktuTotal;
                                chartPermintaanPerWaktu.data.datasets[1].data = permintaanPerWaktuRajal;
                                chartPermintaanPerWaktu.data.datasets[2].data = permintaanPerWaktuRanap;
                                chartPermintaanPerWaktu.update();
                            }


                        } catch (error) {
                            console.error("Error processing response:", error);
                        }
                    },
                    complete: function() {
                        $("#globalLoading").hide();
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                    }
                });
            }
        }


        function renderChartRawatJalanInap(ctx, data, isRawatInap = false) {
            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Selesai', 'Belum'],
                    datasets: [{
                        data: data,
                        backgroundColor: ['rgba(37, 99, 235, 1)', '#D32F2F'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '65%',
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
                            color: '#ffffff',
                            font: {
                                family: 'Inter, sans-serif',
                                weight: 'bold',
                                size: 12
                            },
                            align: 'center',
                            formatter: function(value, context) {
                                return `${value}`;
                            }
                        }
                    },
                },
                plugins: [{
                    id: 'centerText',
                    beforeDraw: function(chart) {
                        let width = chart.width,
                            height = chart.height,
                            ctx = chart.ctx;
                        ctx.restore();

                        let fontSize = (height / 10).toFixed(2);
                        ctx.font = 'bold ' + fontSize + "px sans-serif";
                        ctx.textBaseline = "middle";
                        ctx.textAlign = "center";

                        let completed = chart.data.datasets[0].data[0];
                        let textX = width / 2,
                            textY = height / 2;

                        ctx.fillStyle = "#1e40af";
                        ctx.fillText(completed, textX, textY - fontSize / 2 - 10);
                        ctx.fillText("Selesai", textX, textY + fontSize / 2 - 10);
                        ctx.save();
                    }
                }]
            });
        }

        function renderChartDistribusiRanapRajal(ctx, labels, data, tipe) {
            return new Chart(ctx, {
                type: tipe,
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 1)',    // Red
                            'rgba(54, 162, 235, 1)',    // Blue
                            'rgba(255, 159, 64, 1)',    // Orange
                            'rgba(75, 192, 192, 1)',    // Teal
                            'rgba(153, 102, 255, 1)',   // Purple
                            'rgba(255, 205, 86, 1)',    // Yellow
                            'rgba(39, 174, 96, 1)',     // Green
                            'rgba(52, 152, 219, 1)',    // Sky Blue
                            'rgba(244, 67, 54, 1)',     // Dark Red
                            'rgba(231, 233, 237, 1)'    // Light Gray
                        ],
                        borderColor: '#ffffff',         // White border for contrast
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
        }

        function renderChartDistribusiPemeriksaan(ctx, labels, data, tipe) {
            return new Chart(ctx, {
                type: tipe,
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 1)',  // Red
                            'rgba(54, 162, 235, 1)',  // Blue
                            'rgba(255, 159, 64, 1)',  // Orange
                            'rgba(75, 192, 192, 1)',  // Teal
                            'rgba(153, 102, 255, 1)', // Purple
                            'rgba(255, 159, 64, 1)',  // Orange
                            'rgba(255, 99, 132, 1)',  // Red
                            'rgba(54, 162, 235, 1)'   // Blue
                        ],
                        borderColor: [
                            '#ff6384',  // Red
                            '#36a2eb',  // Blue
                            '#ff9f40',  // Orange
                            '#4bc0c0',  // Teal
                            '#9966ff',  // Purple
                            '#ff9f40',  // Orange
                            '#ff6384',  // Red
                            '#36a2eb'   // Blue
                        ],


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
                            color: '#ffffff',
                            font: {
                                family: 'Inter, sans-serif',
                                weight: 'bold',
                                size: 10,
                            },
                            anchor: 'end',
                            align: 'start',
                            padding: 2,
                            offset: 5,
                            formatter: function(value, context) {

                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = (value / total * 100).toFixed(1) + '%';
                                return percentage;
                            }
                        }
                    }
                }
            });
        }

        function renderChartDistribusiSpesimen(ctx, labels, data, tipe) {
            return new Chart(ctx, {
                type: tipe,
                data: {
                    labels: labels,
                    datasets: [{
                        label: '',
                        data: data,

                        
                        backgroundColor: [
                            'rgba(255, 99, 132, 1)',  // Red
                            'rgba(54, 162, 235, 1)',  // Blue
                            'rgba(255, 159, 64, 1)',  // Orange
                            'rgba(75, 192, 192, 1)',  // Teal
                            'rgba(153, 102, 255, 1)', // Purple
                            'rgba(255, 159, 64, 1)',  // Orange
                            'rgba(255, 99, 132, 1)',  // Red
                            'rgba(54, 162, 235, 1)'   // Blue
                        ],
                        borderColor: [
                            '#ff6384',  // Red
                            '#36a2eb',  // Blue
                            '#ff9f40',  // Orange
                            '#4bc0c0',  // Teal
                            '#9966ff',  // Purple
                            '#ff9f40',  // Orange
                            '#ff6384',  // Red
                            '#36a2eb'   // Blue
                        ],

                        borderRadius: 5,
                        borderWidth: 2
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        x: {
                            stacked: false,
                            ticks: {
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(59, 130, 246, 0.1)'
                            }
                        },
                        y: {
                            stacked: false,
                            ticks: {
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(59, 130, 246, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            color: '#1e40af',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 12,
                                weight: 'bold'
                            },
                            formatter: function(value) {
                                return value;
                            }
                        }
                    }
                }
            });
        }

        function renderChartAverageTAT(ctx, labels, data, tipe) {
            return new Chart(ctx, {
                type: tipe,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'TAT dalam menit',
                        data: data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 1)',  // Red
                            'rgba(54, 162, 235, 1)',  // Blue
                            'rgba(255, 159, 64, 1)',  // Orange
                            'rgba(75, 192, 192, 1)',  // Teal
                            'rgba(153, 102, 255, 1)', // Purple
                            'rgba(255, 159, 64, 1)',  // Orange
                            'rgba(255, 99, 132, 1)',  // Red
                            'rgba(54, 162, 235, 1)'   // Blue
                        ],
                        borderColor: [
                            '#ff6384',  // Red
                            '#36a2eb',  // Blue
                            '#ff9f40',  // Orange
                            '#4bc0c0',  // Teal
                            '#9966ff',  // Purple
                            '#ff9f40',  // Orange
                            '#ff6384',  // Red
                            '#36a2eb'   // Blue
                        ],

                        borderRadius: 5,
                        borderWidth: 2
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        x: {
                            stacked: false,
                            ticks: {
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(59, 130, 246, 0.1)'
                            }
                        },
                        y: {
                            stacked: false,
                            ticks: {
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(59, 130, 246, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            color: '#1e40af',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 12,
                                weight: 'bold'
                            },
                            formatter: function(value) {
                                return value;
                            }
                        }
                    }
                }

            });
        }

        function renderChartPermintaanPemeriksaan(ctx, labels, dataSelesai, dataBelum, tipe) {
            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Selesai',
                            data: dataSelesai,
                            backgroundColor: [
                                'rgba(255, 99, 132, 1)',    // Red
                                'rgba(54, 162, 235, 1)',    // Blue
                                'rgba(255, 159, 64, 1)',    // Orange
                                'rgba(75, 192, 192, 1)',    // Teal
                                'rgba(153, 102, 255, 1)',   // Purple
                                'rgba(255, 205, 86, 1)',    // Yellow
                                'rgba(231, 233, 237, 1)',   // Light Gray
                                'rgba(244, 67, 54, 1)',     // Dark Red
                                'rgba(39, 174, 96, 1)',     // Green
                                'rgba(52, 152, 219, 1)'     // Sky Blue
                            ],
                        },
                        {
                            label: 'Belum',
                            data: dataBelum,
                            backgroundColor: [
                                'rgba(204, 51, 99, 1)',     // Dark Red
                                'rgba(33, 92, 162, 1)',     // Dark Blue
                                'rgba(204, 102, 51, 1)',    // Dark Orange
                                'rgba(33, 132, 132, 1)',    // Dark Teal
                                'rgba(102, 51, 204, 1)',    // Dark Purple
                                'rgba(204, 153, 0, 1)',     // Dark Yellow
                                'rgba(169, 169, 169, 1)',   // Dark Gray
                                'rgba(204, 0, 0, 1)',       // Burgundy
                                'rgba(25, 120, 0, 1)',      // Forest Green
                                'rgba(44, 62, 80, 1)'       // Charcoal Blue
                            ],
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'rect',
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 13,
                                    weight: '600'
                                },
                                padding: 10
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1e3a8a',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            titleFont: {
                                family: 'Inter, sans-serif',
                                size: 14,
                                weight: '600'
                            },
                            bodyFont: {
                                family: 'Inter, sans-serif',
                                size: 10
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            color: '#1e40af',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 12,
                                weight: 'bold'
                            },
                            formatter: function(value) {
                                return value;
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: false,
                            ticks: {
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 11,
                                    weight: '600'
                                }
                            },
                            grid: {
                                color: 'rgba(59, 130, 246, 0.1)'
                            }
                        },
                        y: {
                            stacked: false,
                            ticks: {
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(59, 130, 246, 0.1)'
                            }
                        }
                    },

                }
            });
        }

        function renderChartPermintaanPerWaktu(ctx, labels, dataTotal, dataRajal, dataRanap, tipe) {
            return new Chart(ctx, {
                type: tipe,
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Jumlah Permintaan',
                            data: dataTotal,
                            borderColor: '#d32f2f', // Red for contrast
                            backgroundColor: 'rgba(211, 47, 47, 0.1)', // Lighter red
                            fill: true,
                            pointRadius: 4,
                            pointBackgroundColor: '#e57373', // Light red for points
                            pointBorderColor: '#d32f2f', // Red for points
                            tension: 0.3
                        },
                        {
                            label: 'Rawat Jalan',
                            data: dataRajal,
                            borderColor: '#0288d1', // Blue for contrast
                            backgroundColor: 'rgba(2, 136, 209, 0.1)', // Light blue
                            fill: true,
                            pointRadius: 4,
                            pointBackgroundColor: '#4fa3d1', // Light blue for points
                            pointBorderColor: '#0288d1', // Blue for points
                            tension: 0.3
                        },
                        {
                            label: 'Rawat Inap',
                            data: dataRanap,
                            borderColor: '#7b1fa2', // Purple for contrast
                            backgroundColor: 'rgba(123, 31, 162, 0.1)', // Light purple
                            fill: true,
                            pointRadius: 4,
                            pointBackgroundColor: '#9c4dcc', // Light purple for points
                            pointBorderColor: '#7b1fa2', // Purple for points
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
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
                            bodyColor: '#ffffff'
                        },
                        datalabels: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(59, 130, 246, 0.1)'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#1e40af',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            grid: {
                                color: 'rgba(59, 130, 246, 0.1)'
                            }
                        }
                    }
                }
            });
        }


        function renderTableNilaiKritis(data) {
            const monitoringTable = document.getElementById("nilai-kritis");
            const tableContainer = document.querySelector('.overflow-y-auto');

            if (!monitoringTable || !tableContainer) {
                return;
            }

            monitoringTable.innerHTML = "";

            data.forEach((item) => {
                const rowClass = item.od_tr_flag === 'HH' ? 'bg-red-50 border-l-4 border-red-500 animate-pulse' : 'bg-yellow-50 border-l-4 border-yellow-500 animate-pulse';
                const row = `<tr class="border-b ${rowClass}">
                    <td class="p-2">${item.oh_pid}</td>
                    <td class="p-2">${item.oh_last_name}</td>
                    <td class="p-2">${item.ti_name}</td>
                    <td class="p-2 font-bold">${item.od_tr_val}</td>
                    <td class="p-2"><span class="text-red-600 font-bold">${item.od_tr_flag}</span></td>
                </tr>`;
                monitoringTable.innerHTML += row;
            });

            // Setelah data diupdate, scroll ke atas (awal) sebelum melakukan scrolling otomatis ke bawah
            tableContainer.scrollTop = 0;

            const scrollDown = () => {
                if (tableContainer.scrollTop + tableContainer.clientHeight < tableContainer.scrollHeight) {
                    tableContainer.scrollTop += 2;
                } else {
                    tableContainer.scrollTop = 0; // Kembali ke awal jika sudah mencapai bawah
                }
            };

            const scrollInterval = setInterval(scrollDown, 350);
        }


        document.addEventListener("DOMContentLoaded", function() {

            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDate').value = today;
            document.getElementById('endDate').value = today;

            chartRawatJalan = renderChartRawatJalanInap('donutChartRawatJalan', [0, 0], true);
            chartRawatInap = renderChartRawatJalanInap('donutChartRawatInap', [0, 0], true);

            chartDistribusiPemeriksaan = renderChartDistribusiPemeriksaan(document.getElementById('distribusiPemeriksaanChart').getContext('2d'), [], [], 'pie');
            chartDistribusiRanapRajal = renderChartDistribusiRanapRajal(document.getElementById('distribusiRanapRajalChart').getContext('2d'), [], [], 'pie');

            chartDistribusiSpesimen = renderChartDistribusiSpesimen(document.getElementById('distribusiSpesimenChart').getContext('2d'), [], [], 'bar');
            chartAverageTAT = renderChartAverageTAT(document.getElementById('averageTATChart').getContext('2d'), [], [], 'bar');

            chartPermintaanPemeriksaan = renderChartPermintaanPemeriksaan(document.getElementById('permintaanPemeriksaanChart').getContext('2d'), [], [], [], 'bar');
            chartPermintaanPerWaktu = renderChartPermintaanPerWaktu(document.getElementById('permintaanPerWaktuChart').getContext('2d'), [], [], [], [], 'line');

            fetchDashboardData();

            let countdownTime = 300;
            let countdownTimer;

            function startCountdown() {
                countdownTimer = setInterval(function() {
                    if (countdownTime <= 0) {
                        clearInterval(countdownTimer);
                        fetchDashboardData();
                        countdownTime = 300;
                        startCountdown();
                    } else {

                        $("#countdownDisplay").text(`update in: ${countdownTime} seconds`);
                        countdownTime--;
                    }
                }, 1000);
            }

            document.getElementById("fetchDataButton").addEventListener("click", function() {
                $("#globalLoading").show();
                fetchDashboardData();
            });

            startCountdown();
            setInterval(fetchDashboardData, 300000); 
        });
    </script>

</x-app-layout>