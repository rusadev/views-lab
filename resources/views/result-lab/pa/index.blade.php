@section('title', 'Laboratorium Patologi Anatomi')

<x-app-layout>
    <div class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-slate-200 rounded p-8 text-center space-y-4">
                <div class="space-y-1">
                    <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-teal-50 text-teal-800 border border-teal-200">Dalam Tahap Pengembangan (v2.0)</span>
                    <h1 class="text-xl font-bold text-slate-900">Laboratorium Patologi Anatomi</h1>
                    <p class="text-xs text-slate-500 max-w-md mx-auto">Modul pemeriksaan histopatologi, sitopatologi, dan biopsi jaringan sedang disiapkan.</p>
                </div>

                <div class="pt-2">
                    <a href="{{ route('klinik.index') }}" 
                       class="inline-block px-4 py-2 text-xs font-semibold rounded text-white bg-blue-600 hover:bg-blue-700 border border-blue-700 transition-colors">
                        &larr; Buka Patologi Klinik
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>