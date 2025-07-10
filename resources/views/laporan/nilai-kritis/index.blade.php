<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-400 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <i class="fas fa-exclamation-circle text-white"></i>
            {{ __('Laporan Nilai Kritis') }}
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
                        Laporan Nilai Kritis
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Tabel berikut menyajikan data nilai kritis pasien berdasarkan periode yang dipilih.
                    </p>
                    <table id="nilaiKritisTable" class="table-auto w-full border-collapse border border-gray-300 mt-4 text-sm">
                        <thead id="tableHeadnilaiKritis"></thead>
                        <tbody id="tableBodynilaiKritis"></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchButton = document.getElementById("search-button");
            const startDateInput = document.getElementById("start_date");
            const endDateInput = document.getElementById("end_date");
            const tableHead = document.getElementById("tableHeadnilaiKritis");
            const tableBody = document.getElementById("tableBodynilaiKritis");

            const BASE_URL = "{{ config('app.url') }}";
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

                searchButton.disabled = true;
                searchButton.innerHTML = `
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Memuat...</span>
                `;


                // Tampilkan teks loading sebelum fetch data
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-gray-500 py-4">Loading data...</td>
                    </tr>
                `;

                try {
                    const response = await fetch(`/laboratorium/laporan/nilai-kritis/data?start_date=${startDate}&end_date=${endDate}`);
                    const data = await response.json();

                    if (data.length > 0) {
                        renderTable(data);
                    } else {
                        showNoDataMessage();
                    }
                } catch (error) {
                    console.error("Error fetching data:", error);
                    showErrorMessage();
                } finally {
                    searchButton.disabled = false;
                    searchButton.innerHTML = `
                        <i class="fas fa-search"></i>
                        <span>Generate Laporan</span>
                    `;
                }
            });

            function renderTable(data) {
                tableHead.innerHTML = `
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2">Tanggal</th>
                    <th class="border px-4 py-2">No. Order</th>
                    <th class="border px-4 py-2">ID Pasien</th>
                    <th class="border px-4 py-2">Nama Pasien</th>
                    <th class="border px-4 py-2">Dokter</th>
                    <th class="border px-4 py-2">Klinik</th>
                    <th class="border px-4 py-2">Pemeriksaan</th>
                    <th class="border px-4 py-2">Hasil</th>
                    <th class="border px-4 py-2">Flag</th>
                    <th class="border px-4 py-2">Waktu Pemeriksaan</th>
                    <th class="border px-4 py-2">Pelapor</th>
                    <th class="border px-4 py-2">Keterangan</th>
                </tr>
            `;

                tableBody.innerHTML = data.map(item => `
                <tr class="border text-center">
                    <td class="border px-4 py-2">${item.oh_trx_dt}</td>
                    <td class="border px-4 py-2">${item.oh_tno}</td>
                    <td class="border px-4 py-2">${item.oh_pid}</td>
                    <td class="border px-4 py-2">${item.oh_last_name}</td>
                    <td class="border px-4 py-2">${item.oh_dname}</td>
                    <td class="border px-4 py-2">${item.clinic_desc}</td>
                    <td class="border px-4 py-2">${item.ti_name}</td>
                    <td class="border px-4 py-2">${item.od_tr_val}</td>
                    <td class="border px-4 py-2 font-bold ${item.od_tr_flag === 'LL' ? 'text-orange-500' : 'text-red-500'}">
                    ${item.od_tr_flag}
                    </td>
                    <td class="border px-4 py-2">${item.od_update_on}</td>
                    <td class="border px-4 py-2">${item.od_validate_by}</td>
                    <td class="border px-4 py-2">${item.od_tr_comment}</td>
                </tr>
            `).join('');
            }

            function showNoDataMessage() {
                tableBody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center text-gray-500 py-4">Tidak ada data ditemukan.</td>
                </tr>
            `;
            }

            function showErrorMessage() {
                tableBody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center text-red-500 py-4">Gagal mengambil data, silakan coba lagi.</td>
                </tr>
            `;
            }

            function formatDate(dateString) {
                return new Date(dateString).toLocaleDateString('id-ID', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
        });
    </script>


</x-app-layout>