@section('title', 'Detail Hasil Lab: ' . ($orderHeader->name ?? 'Pasien') . ' (' . ($orderHeader->pid ?? '') . ')')

<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <!-- Action Bar (Hidden on Print, Flat) -->
            <div class="no-print bg-white border border-slate-200 rounded p-3.5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <a href="{{ route('klinik.index') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded text-slate-800 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition-colors">
                        &larr; Kembali ke Pencarian
                    </a>
                    <span class="text-slate-300">|</span>
                    <span class="text-xs font-medium text-slate-600">
                        No. LAB: <strong class="text-slate-900 font-mono">{{ $orderHeader->tno }}</strong> &bull; No. RM: <strong class="text-slate-900 font-mono">{{ $orderHeader->pid }}</strong>
                    </span>
                </div>

                <div>
                    <button onclick="window.print()" 
                            class="px-4 py-1.5 text-xs font-semibold rounded text-white bg-blue-600 hover:bg-blue-700 border border-blue-700 transition-colors">
                        Cetak Hasil (Print)
                    </button>
                </div>
            </div>

            <!-- Printable Laboratory Report Container (Flat) -->
            <div class="bg-white border border-slate-200 rounded p-6 space-y-5">
                
                <!-- Report Header -->
                <div class="border-b border-slate-200 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('img/logo.png') }}" alt="Logo" class="h-12 w-auto object-contain">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 tracking-tight">INSTALASI LABORATORIUM PATOLOGI KLINIK</h2>
                            <p class="text-xs font-medium text-slate-500">RSUD - Hasil Pemeriksaan Terkomputerisasi</p>
                        </div>
                    </div>

                    <div class="text-left md:text-right">
                        <div class="inline-block px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-300">
                            Hasil Terverifikasi
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 font-mono">Tgl Validasi: {{ $orderHeader->validate_on ?? '-' }}</p>
                    </div>
                </div>

                <!-- Patient & Order Metadata Grid (Flat) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    
                    <!-- Left: Patient Information -->
                    <div class="bg-slate-50 rounded p-3.5 border border-slate-200">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-blue-800 mb-2 border-b border-slate-200 pb-1">
                            Informasi Pasien
                        </div>
                        <table class="w-full text-xs border-collapse space-y-1">
                            <tbody>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium w-36">No. Rekam Medis</td>
                                    <td class="py-0.5 text-slate-400 w-3">:</td>
                                    <td class="py-0.5 text-slate-900 font-bold font-mono">{{ $orderHeader->pid ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium">Nama Pasien</td>
                                    <td class="py-0.5 text-slate-400">:</td>
                                    <td class="py-0.5 text-slate-900 font-bold">{{ $orderHeader->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium">Tanggal Lahir / Usia</td>
                                    <td class="py-0.5 text-slate-400">:</td>
                                    <td class="py-0.5 text-slate-800">{{ $orderHeader->bod ?? '-' }} ({{ $orderHeader->calculated_age ?? '-' }})</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium">Jenis Kelamin</td>
                                    <td class="py-0.5 text-slate-400">:</td>
                                    <td class="py-0.5 text-slate-800">
                                        @if(($orderHeader->gender ?? '') === 'M' || ($orderHeader->gender ?? '') === 'L')
                                            Laki-laki (L)
                                        @elseif(($orderHeader->gender ?? '') === 'F' || ($orderHeader->gender ?? '') === 'P')
                                            Perempuan (P)
                                        @else
                                            {{ $orderHeader->gender ?? '-' }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium align-top">Alamat</td>
                                    <td class="py-0.5 text-slate-400 align-top">:</td>
                                    <td class="py-0.5 text-slate-700">
                                        {{ implode(', ', array_filter([$orderHeader->addr1, $orderHeader->addr2, $orderHeader->addr3, $orderHeader->addr4])) ?: '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Right: Order & Doctor Information -->
                    <div class="bg-slate-50 rounded p-3.5 border border-slate-200">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-blue-800 mb-2 border-b border-slate-200 pb-1">
                            Informasi Pemeriksaan
                        </div>
                        <table class="w-full text-xs border-collapse space-y-1">
                            <tbody>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium w-36">No. Order / LAB</td>
                                    <td class="py-0.5 text-slate-400 w-3">:</td>
                                    <td class="py-0.5 text-slate-900 font-bold font-mono">{{ $orderHeader->ono ?? '-' }} / {{ $orderHeader->tno ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium">Dokter Pengirim</td>
                                    <td class="py-0.5 text-slate-400">:</td>
                                    <td class="py-0.5 text-slate-900 font-semibold">{{ $orderHeader->clinician ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium">Ruangan / Asal</td>
                                    <td class="py-0.5 text-slate-400">:</td>
                                    <td class="py-0.5 text-slate-800">{{ $orderHeader->room_desc ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium">Tgl Permintaan</td>
                                    <td class="py-0.5 text-slate-400">:</td>
                                    <td class="py-0.5 text-slate-800 font-mono">{{ $orderHeader->order_date ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium">Tgl Spesimen / Selesai</td>
                                    <td class="py-0.5 text-slate-400">:</td>
                                    <td class="py-0.5 text-slate-800 font-mono">{{ $orderHeader->spl_rcvdt ?? '-' }} / {{ $orderHeader->validate_on ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0.5 text-slate-500 font-medium">Diagnosa Klinis</td>
                                    <td class="py-0.5 text-slate-400">:</td>
                                    <td class="py-0.5 text-slate-700 italic">{{ $orderHeader->diag1 ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- Laboratory Test Results Table (Flat) -->
                <div class="space-y-2 pt-2">
                    <div class="border-b border-slate-200 pb-1.5">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Hasil Uji Laboratorium
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-100 border-y border-slate-300 text-slate-700 font-bold uppercase text-[11px]">
                                    <th class="py-2 px-2.5 w-2/5">Nama Pemeriksaan</th>
                                    <th class="py-2 px-2 w-14 text-center">Flag</th>
                                    <th class="py-2 px-2.5 w-1/6">Hasil</th>
                                    <th class="py-2 px-2.5 w-1/6">Satuan</th>
                                    <th class="py-2 px-2.5 w-1/6">Nilai Rujukan</th>
                                    <th class="py-2 px-2.5">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-slate-800">
                                @forelse ($groupedOrderDetails as $groupName => $details)
                                
                                <!-- Group Header Row -->
                                <tr class="bg-slate-50 font-bold text-slate-900 border-t border-slate-300">
                                    <td colspan="6" class="py-1.5 px-2.5 uppercase tracking-wide">
                                        {{ $groupName }}
                                    </td>
                                </tr>

                                <!-- Details Rows -->
                                @foreach ($details as $detail)
                                    @if ($detail->test_value !== '!' && $detail->test_value !== '.' && $detail->test_value !== '-')
                                    <tr class="hover:bg-slate-50 {{ $detail->abnormal_flag !== 'N' ? 'bg-rose-50/40' : '' }}">
                                        <!-- Test Name with indentation -->
                                        <td class="py-1.5 px-2.5 {{ $detail->od_item_type === 'P' ? 'pl-6 font-bold text-slate-900' : '' }} {{ $detail->od_item_type === 'U' ? 'pl-10 text-slate-700' : '' }}">
                                            {{ $detail->test_name }}
                                        </td>

                                        <!-- Test Value / Content -->
                                        @if ($detail->od_data_type == 'W')
                                        <td colspan="5" class="py-1.5 px-2.5 font-mono font-medium text-slate-800">
                                            {!! nl2br(e($detail->test_value)) !!}
                                        </td>
                                        @else
                                        <!-- Flag (Abnormal indicator) -->
                                        <td class="py-1.5 px-2 text-center">
                                            @if ($detail->test_value !== 'Belum Tersedia' && $detail->abnormal_flag !== 'N' && !empty($detail->abnormal_flag))
                                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold {{ in_array($detail->abnormal_flag, ['HH', 'H']) ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-blue-100 text-blue-800 border border-blue-300' }}">
                                                    {{ $detail->abnormal_flag }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Hasil (Test Value) -->
                                        <td class="py-1.5 px-2.5 font-mono font-semibold {{ $detail->abnormal_flag !== 'N' ? 'text-rose-800 font-bold' : 'text-slate-900' }}">
                                            @if ($detail->od_data_type !== "X" && $detail->od_data_type !== "P")
                                                {{ $detail->test_value }}
                                            @endif
                                        </td>

                                        <!-- Unit -->
                                        <td class="py-1.5 px-2.5 text-slate-600 font-medium">
                                            @if ($detail->test_value !== "Belum Tersedia" && $detail->od_data_type !== "X" && $detail->od_data_type !== "P")
                                                {{ $detail->test_unit }}
                                            @endif
                                        </td>

                                        <!-- Reference Range -->
                                        <td class="py-1.5 px-2.5 text-slate-600 font-mono text-[11px]">
                                            @if ($detail->ref_range !== 'MRR' && !empty($detail->ref_range))
                                                {!! nl2br(e($detail->ref_range)) !!}
                                            @endif
                                        </td>
                                        @endif

                                        <!-- Comments -->
                                        <td class="py-1.5 px-2.5 text-slate-500 italic text-[11px]">
                                            @if ($detail->test_value !== 'Belum Tersedia')
                                                @if ($detail->test_comment)
                                                    <div>{!! nl2br(e($detail->test_comment)) !!}</div>
                                                @endif
                                                @if ($detail->attached_comment)
                                                    <div>{!! nl2br(e($detail->attached_comment)) !!}</div>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach

                                @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400 text-xs">
                                        Tidak ada item pemeriksaan yang ditemukan untuk order ini.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>