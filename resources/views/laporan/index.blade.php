<title>@yield('title', 'Laporan Patologi Klinik')</title>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-purple-600 to-blue-500 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <svg class="w-6 h-6 text-white animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h6m4 12H5a2 2 0 01-2-2V5a2 2 0 012-2h9l5 5v12a2 2 0 01-2 2z" />
            </svg>
            {{ __('Laporan Patologi Klinik') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-4 text-gray-900">
                    <!-- Form Pencarian -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

                        <!-- Card for Laporan Jumlah Pasien -->
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-400 p-6 rounded-xl shadow-2xl transform hover:scale-105 transition duration-300 ease-in-out">
                            <div class="flex items-center space-x-4">
                                <i class="fas fa-user-injured text-white text-4xl"></i>
                                <div>
                                    <h3 class="text-xl font-semibold text-white">Jumlah Pasien</h3>
                                </div>
                            </div>
                            <a href="{{route('laporan.jumlah-pasien.index')}}" class="mt-4 inline-block text-white font-medium hover:text-indigo-200">Lihat Laporan</a>
                        </div>

                        <!-- Card for Laporan Per Test Pemeriksaan -->
                        <div class="bg-gradient-to-r from-teal-600 to-teal-400 p-6 rounded-xl shadow-2xl transform hover:scale-105 transition duration-300 ease-in-out">
                            <div class="flex items-center space-x-4">
                                <i class="fas fa-microscope text-white text-4xl"></i>
                                <div>
                                    <h3 class="text-xl font-semibold text-white">Jumlah Pemeriksaan</h3>
                                </div>
                            </div>
                            <a href="{{route('laporan.jumlah-pemeriksaan.index')}}" class="mt-4 inline-block text-white font-medium hover:text-teal-200">Lihat Laporan</a>
                        </div>

                        <!-- Card for Laporan Hasil Histopatologi -->
                        <!-- <div class="bg-gradient-to-r from-yellow-600 to-yellow-400 p-6 rounded-xl shadow-2xl transform hover:scale-105 transition duration-300 ease-in-out">
                            <div class="flex items-center space-x-4">
                                
                                <i class="fas fa-flask text-white text-4xl"></i>
                                <div>
                                    <h3 class="text-xl font-semibold text-white">Detail Pemeriksaan</h3>
                                </div>
                            </div>
                            <a href="{{route('laporan.detail-pemeriksaan.index')}}" class="mt-4 inline-block text-white font-medium hover:text-yellow-200">Lihat Laporan</a>
                        </div> -->

                        <!-- Card for Laporan Mikrobiologi -->
                        <div class="bg-gradient-to-r from-purple-600 to-purple-400 p-6 rounded-xl shadow-2xl transform hover:scale-105 transition duration-300 ease-in-out">
                            <div class="flex items-center space-x-4">
                                <i class="fas fa-vial text-white text-4xl"></i>
                                <div>
                                    <h3 class="text-xl font-semibold text-white">Penggunaan Tabung</h3>
                                </div>
                            </div>
                            <a href="{{route('laporan.penggunaan-tabung.index')}}" class="mt-4 inline-block text-white font-medium hover:text-purple-200">Lihat Laporan</a>
                        </div>

                        <!-- Card for Laporan Imunohistokimia -->
                        <div class="bg-gradient-to-r from-pink-600 to-pink-400 p-6 rounded-xl shadow-2xl transform hover:scale-105 transition duration-300 ease-in-out">
                            <div class="flex items-center space-x-4">
                                <i class="fas fa-exclamation-circle text-white text-4xl"></i>
                                <div>
                                    <h3 class="text-xl font-semibold text-white">Laporan Nilai Kritis</h3>
                                </div>
                            </div>
                            <a href="{{route('laporan.nilai-kritis.index')}}" class="mt-4 inline-block text-white font-medium hover:text-pink-200">Lihat Laporan</a>
                        </div>

                        <!-- Card for Laporan Genetik -->
                        <div class="bg-gradient-to-r from-teal-700 to-teal-500 p-6 rounded-xl shadow-2xl transform hover:scale-105 transition duration-300 ease-in-out">
                            <div class="flex items-center space-x-4">
                                <i class="fas fa-hourglass-half text-white text-4xl"></i>
                                <div>
                                    <h3 class="text-xl font-semibold text-white">Laporan TAT</h3>
                                </div>
                            </div>
                            <a href="{{route('laporan.tat.index')}}" class="mt-4 inline-block text-white font-medium hover:text-teal-200">Lihat Laporan</a>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </div>
</x-app-layout>