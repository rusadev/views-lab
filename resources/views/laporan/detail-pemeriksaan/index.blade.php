<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-400 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <svg class="w-6 h-6 text-white animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h6m4 12H5a2 2 0 01-2-2V5a2 2 0 012-2h9l5 5v12a2 2 0 01-2 2z" />
            </svg>
            {{ __('Laporan Detail Pemeriksaan') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4 text-gray-900">
                    <form method="GET" action="#" class="space-y-4">
                        <div class="flex flex-wrap gap-4 items-end">
                            <!-- <div class="w-1/4">
                                <label for="test_pemeriksaan" class="block text-sm font-medium mb-1">Test Pemeriksaan</label>
                                <select id="test_pemeriksaan" name="test_pemeriksaan" class="w-full p-2 border rounded text-sm">
                                    <option value="">Pilih Test</option>
                                    <option value="hematologi">Hematologi</option>
                                    <option value="biokimia">Biokimia</option>
                                    <option value="imunologi">Imunologi</option>
                                </select>
                            </div>

                            <div class="w-1/4">
                                <label for="nama_pemeriksaan" class="block text-sm font-medium mb-1">Nama Pemeriksaan</label>
                                <select id="nama_pemeriksaan" name="nama_pemeriksaan" class="w-full p-2 border rounded text-sm">
                                    <option value="">Pilih Pemeriksaan</option>
                                </select>
                            </div> -->
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
        </div>
    </div>
</x-app-layout>