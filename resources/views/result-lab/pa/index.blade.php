<title>@yield('title', 'Laboratorium Mikrobiologi Klinik')</title>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Hasil Pemeriksaan Laboratorium Mikrobiologi Klinik') }}
        </h2>
    </x-slot>

    <div class="h-screen flex items-center justify-center bg-gradient-to-r from-purple-600 to-blue-500">
        <div class="text-center text-white">
            <h1 class="text-6xl font-bold mb-4 animate-bounce">Coming Soon</h1>
            <p class="text-xl mb-8">Halaman ini sedang dalam pengembangan. Silakan kembali lagi nanti!</p>
            <a href="{{ url('/') }}" class="inline-block bg-white text-purple-600 px-6 py-3 rounded-full font-semibold hover:bg-purple-50 transition-all duration-300 transform hover:scale-105 shadow-lg">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</x-app-layout>