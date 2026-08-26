@section('title', 'Pusat Laporan Laboratorium Patologi Klinik')

<x-app-layout>
    <div class="py-4 bg-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            
            <!-- Page Header Card (Flat v2.0) -->
            <div class="bg-white border border-slate-200 rounded p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-bold text-slate-900">Pusat Laporan Laboratorium Patologi Klinik</h1>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 text-slate-700 border border-slate-300">v2.0</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Rekapitulasi data operasional, statistik pemeriksaan, mutu laboratorium, dan ekspor dokumen resmi.</p>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2.5 py-1 rounded bg-slate-50 text-slate-700 font-medium border border-slate-200">
                        Format Tersedia: <strong>Excel (.xlsx) & Word (.docx)</strong>
                    </span>
                </div>
            </div>

            <!-- Report Cards Grid (Flat 3 cols) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                <!-- Card 1: Jumlah Pasien -->
                <div class="bg-white border border-slate-200 rounded p-5 flex flex-col justify-between hover:border-blue-400 transition-colors">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 rounded uppercase">Demografi & Kunjungan</span>
                            <span class="text-[11px] text-slate-400 font-mono">Lap. 01</span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900">Laporan Jumlah Pasien</h2>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Rekapitulasi data kunjungan pasien per jenis rawat (Rawat Jalan, Rawat Inap, UGD), distribusi usia, jenis kelamin, serta rincian per ruangan/poliklinik.
                        </p>
                        <div class="flex items-center gap-2 mt-3 text-[11px] text-slate-500">
                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-700 font-medium">Grafik Donut</span>
                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-700 font-medium">Tabel Matriks</span>
                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-800 rounded font-bold border border-emerald-200">Excel & Word</span>
                        </div>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a href="{{ route('laporan.jumlah-pasien.index') }}" 
                           class="w-full inline-flex items-center justify-center h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded border border-blue-700 transition-colors">
                            Buka Laporan &rarr;
                        </a>
                    </div>
                </div>

                <!-- Card 2: Jumlah Pemeriksaan -->
                <div class="bg-white border border-slate-200 rounded p-5 flex flex-col justify-between hover:border-blue-400 transition-colors">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 rounded uppercase">Volume Uji</span>
                            <span class="text-[11px] text-slate-400 font-mono">Lap. 02</span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900">Laporan Jumlah Pemeriksaan</h2>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Akumulasi volume permintaan uji laboratorium berdasarkan sub-laboratorium (Hematologi, Kimia, Urinalisa, dll) dan per unit/asal ruangan.
                        </p>
                        <div class="flex items-center gap-2 mt-3 text-[11px] text-slate-500">
                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-700 font-medium">Rekapitulasi Uji</span>
                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-800 rounded font-bold border border-emerald-200">Excel (.xlsx)</span>
                        </div>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a href="{{ route('laporan.jumlah-pemeriksaan.index') }}" 
                           class="w-full inline-flex items-center justify-center h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded border border-blue-700 transition-colors">
                            Buka Laporan &rarr;
                        </a>
                    </div>
                </div>

                <!-- Card 3: Penggunaan Tabung -->
                <div class="bg-white border border-slate-200 rounded p-5 flex flex-col justify-between hover:border-blue-400 transition-colors">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 rounded uppercase">Inventaris Spesimen</span>
                            <span class="text-[11px] text-slate-400 font-mono">Lap. 03</span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900">Laporan Penggunaan Tabung</h2>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Pemantauan konsumsi tabung vakum sampel darah (EDTA, Serum / Clot Activator, Heparin, Sitrat) dan spesimen non-darah (Urine, Feses).
                        </p>
                        <div class="flex items-center gap-2 mt-3 text-[11px] text-slate-500">
                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-700 font-medium">Logistik Lab</span>
                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-800 rounded font-bold border border-emerald-200">Excel (.xlsx)</span>
                        </div>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a href="{{ route('laporan.penggunaan-tabung.index') }}" 
                           class="w-full inline-flex items-center justify-center h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded border border-blue-700 transition-colors">
                            Buka Laporan &rarr;
                        </a>
                    </div>
                </div>

                <!-- Card 4: Nilai Kritis -->
                <div class="bg-white border border-slate-200 rounded p-5 flex flex-col justify-between hover:border-rose-400 transition-colors">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 rounded uppercase">Mutu & Keselamatan</span>
                            <span class="text-[11px] text-slate-400 font-mono">Lap. 04</span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900">Laporan Nilai Kritis</h2>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Dokumentasi riwayat pelaporan hasil tes bernilai kritis (HH / LL) untuk kebutuhan audit keselamatan pasien dan evaluasi klinis dokter.
                        </p>
                        <div class="flex items-center gap-2 mt-3 text-[11px] text-slate-500">
                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-700 font-medium">Audit Mutu</span>
                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-800 rounded font-bold border border-emerald-200">Excel & Word</span>
                        </div>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a href="{{ route('laporan.nilai-kritis.index') }}" 
                           class="w-full inline-flex items-center justify-center h-9 px-4 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded border border-rose-700 transition-colors">
                            Buka Laporan &rarr;
                        </a>
                    </div>
                </div>

                <!-- Card 5: Turn Around Time (TAT) -->
                <div class="bg-white border border-slate-200 rounded p-5 flex flex-col justify-between hover:border-blue-400 transition-colors">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200 rounded uppercase">Efisiensi & SLA</span>
                            <span class="text-[11px] text-slate-400 font-mono">Lap. 05</span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900">Laporan Turn Around Time (TAT)</h2>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Evaluasi durasi kecepatan penyelesaian pengujian dari sampel diterima hingga validasi dokter penanggung jawab laboratorium.
                        </p>
                        <div class="flex items-center gap-2 mt-3 text-[11px] text-slate-500">
                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-700 font-medium">Standar SLA</span>
                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-800 rounded font-bold border border-emerald-200">Excel (.xlsx)</span>
                        </div>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a href="{{ route('laporan.tat.index') }}" 
                           class="w-full inline-flex items-center justify-center h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded border border-blue-700 transition-colors">
                            Buka Laporan &rarr;
                        </a>
                    </div>
                </div>

                <!-- Card 6: Detail Pemeriksaan -->
                <div class="bg-white border border-slate-200 rounded p-5 flex flex-col justify-between hover:border-blue-400 transition-colors">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 rounded uppercase">Rincian Transaksi Pasien</span>
                            <span class="text-[11px] text-slate-400 font-mono">Lap. 06</span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900">Laporan Detail Pemeriksaan</h2>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Daftar riwayat lengkap hasil pemeriksaan per pasien, mencakup nomor rekam medis, nama uji, nilai kuantitatif, dan status validasi.
                        </p>
                        <div class="flex items-center gap-2 mt-3 text-[11px] text-slate-500">
                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-700 font-medium">Log Transaksi</span>
                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-800 rounded font-bold border border-emerald-200">Excel (.xlsx)</span>
                        </div>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100">
                        <a href="{{ route('laporan.detail-pemeriksaan.index') }}" 
                           class="w-full inline-flex items-center justify-center h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded border border-blue-700 transition-colors">
                            Buka Laporan &rarr;
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>