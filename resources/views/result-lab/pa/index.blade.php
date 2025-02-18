<title>@yield('title', 'Laboratorium Patologi Anatomi')</title>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-purple-600 to-blue-500 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <svg class="w-6 h-6 text-white animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h6m4 12H5a2 2 0 01-2-2V5a2 2 0 012-2h9l5 5v12a2 2 0 01-2 2z" />
            </svg>
            {{ __('Hasil Pemeriksaan Laboratorium Patologi Anatomi') }}
        </h2>
    </x-slot>

    <!-- <div class="h-screen flex items-center justify-center bg-gradient-to-r from-purple-600 to-blue-500">
        <div class="text-center text-white">
            <h1 class="text-6xl font-bold mb-4 animate-bounce">Coming Soon</h1>
            <p class="text-xl mb-8">Halaman ini sedang dalam pengembangan. Silakan kembali lagi nanti!</p>
            <a href="{{ url('/') }}" class="inline-block bg-white text-purple-600 px-6 py-3 rounded-full font-semibold hover:bg-purple-50 transition-all duration-300 transform hover:scale-105 shadow-lg">
                Kembali ke Beranda
            </a>
        </div>
    </div> -->

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4 text-gray-900">
                    <!-- Form Pencarian -->
                    <form method="GET" action="#" class="space-y-4">
                        <div>
                            <label class="font-bold bg-gradient-to-r from-purple-600 to-blue-500 text-transparent bg-clip-text">
                                <i class="fas fa-filter"></i> Pencarian Berdasarkan:
                            </label>
                            <hr class="my-2">
                            <div class="flex gap-4 mt-1">
                                <label class="flex items-center text-sm font-medium hover:text-blue-600 transition">
                                    <input type="radio" name="search_type" value="rm" class="form-radio text-blue-600 focus:ring-blue-500" checked>
                                    <span class="ml-2">
                                        <i class="fas fa-id-card"></i> Nomor RM
                                    </span>
                                </label>
                                <label class="flex items-center text-sm font-medium hover:text-green-600 transition">
                                    <input type="radio" name="search_type" value="ruangan" class="form-radio text-green-600 focus:ring-green-500">
                                    <span class="ml-2">
                                        <i class="fas fa-hospital"></i> Ruangan
                                    </span>
                                </label>
                            </div>
                        </div>


                        <!-- Input Nomor RM dan Ruangan -->
                        <div class="flex flex-wrap gap-4 items-end">
                            <!-- Nomor RM -->
                            <div id="rm_section" class="w-1/2">
                                <label for="rm_number" class="block text-sm font-medium mb-1">Nomor RM</label>
                                <input type="text" id="rm_number" name="rm_number" class="w-full p-2 border rounded text-sm" placeholder="Masukan Nomor RM Pasien...">
                            </div>

                            <!-- Ruangan -->
                            <div id="ruangan_section" class="hidden w-1/4">
                                <label for="ruangan" class="block text-sm font-medium mb-1 select2">Ruangan</label>
                                <select id="ruangan" class="w-full border rounded text-sm">
                                    <option selected>Pilih Ruangan</option>
                                </select>
                            </div>

                            <!-- Tanggal Mulai & Tanggal Selesai -->
                            <div id="date_range_section" class="hidden flex w-1/4 gap-2">
                                <div class="w-1/2">
                                    <label for="start_date" class="block text-sm font-medium mb-1">Tanggal Mulai</label>
                                    <input type="date" id="start_date" class="w-full p-2 border rounded text-sm">
                                </div>
                                <div class="w-1/2">
                                    <label for="end_date" class="block text-sm font-medium mb-1">Tanggal Selesai</label>
                                    <input type="date" id="end_date" class="w-full p-2 border rounded text-sm">
                                </div>
                            </div>
                        </div>

                        <div>
                            <button id="search-button" class="bg-gradient-to-r from-purple-600 to-blue-500 hover:from-purple-600 hover:to-blue-800 text-white text-sm font-semibold px-3 py-2 rounded flex items-center gap-2 shadow-lg transition-all duration-300">
                                <i id="search-icon" class="fas fa-search"></i>
                                <span id="search-text">Cari Hasil Pemeriksaan</span>
                            </button>

                        </div>
                    </form>
                </div>
            </div>

            <!-- Informasi Order -->
            <div class="bg-white shadow-sm rounded-lg mt-4">
                <div class="p-4 text-gray-900 relative">
                    <div id="loading-overlay-order" class="hidden absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 z-10">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                            <span class="text-gray-700 font-semibold">Memuat Data...</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <!-- <span id="criticalTableTitle" class="text-xl font-bold">Daftar Pemeriksaan Laboratorium</span> -->
                        <span id="criticalTableTitle" class="text-xl font-bold bg-gradient-to-r from-purple-600 to-blue-500 text-transparent bg-clip-text">
                            Daftar Pemeriksaan Laboratorium
                        </span>
                        <hr class="my-2">

                        <table id="orderTable" class="w-full border text-sm relative z-0 min-w-max">
                            <thead>
                                <tr class="bg-grey-400">
                                    <th class="border px-2 py-1">Status Pemeriksaan </th>
                                    <th class="border px-2 py-1">Tanggal Permintaan</th>
                                    <th class="border px-2 py-1">Nomor SIMRS</th>
                                    <th class="border px-2 py-1">Nomor LAB</th>
                                    <th class="border px-2 py-1">Nomor RM</th>
                                    <th class="border px-2 py-1">Nama Pasien</th>
                                    <th class="border px-2 py-1">Ruangan</th>
                                    <th class="border px-2 py-1">Dokter Pengirim</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>


        </div>
    </div>
</x-app-layout>