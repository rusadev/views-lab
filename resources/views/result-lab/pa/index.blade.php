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