<title>@yield('title', 'Laboratorium Patologi Klinik - Details')</title>


<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white bg-gradient-to-r from-blue-500 to-blue-700 px-4 py-3 rounded-md shadow-md flex items-center gap-2 transition-all duration-300 hover:shadow-lg hover:brightness-110">
            <svg class="w-6 h-6 text-white animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h6m4 12H5a2 2 0 01-2-2V5a2 2 0 012-2h9l5 5v12a2 2 0 01-2 2z" />
            </svg>
            {{ __('Hasil Pemeriksaan Laboratorium Patologi Klinik') }}
        </h2>
    </x-slot>


    <div class="py-2">
        <div class="max-w-7xl mx-auto px-2">
            <div class="bg-white shadow-md rounded-lg p-4">
                <!-- Order Header Section -->
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <!-- Bagian Kiri: Informasi Pasien -->
                    <div class="p-3">
                        <table class="w-full text-sm border-collapse">
                            <tbody>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap w-40">Nomor Laboratorium</td>
                                    <td class="px-2 py-0 w-4">:</td>
                                    <td class="px-2 py-0 text-gray-800 w-full">{{ $orderHeader->ono }} / {{ $orderHeader->tno }}</td>
                                </tr>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap">Nomor Rekam Medis</td>
                                    <td class="px-2 py-0">:</td>
                                    <td class="px-2 py-0 text-gray-800">{{ $orderHeader->pid }}</td>
                                </tr>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap">Nama Lengkap</td>
                                    <td class="px-2 py-0">:</td>
                                    <td class="px-2 py-0 text-gray-800">{{ $orderHeader->name }}</td>
                                </tr>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap">Tanggal Lahir</td>
                                    <td class="px-2 py-0">:</td>
                                    <td class="px-2 py-0 text-gray-800">{{ $orderHeader->bod }} / {{ $orderHeader->calculated_age }}</td>
                                </tr>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap">Jenis Kelamin</td>
                                    <td class="px-2 py-0">:</td>
                                    <td class="px-2 py-0 text-gray-800">{{ $orderHeader->gender }}</td>
                                </tr>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap">Alamat</td>
                                    <td class="px-2 py-0">:</td>
                                    <td class="px-2 py-0 text-gray-800">
                                        {{ implode(', ', array_filter([$orderHeader->addr1, $orderHeader->addr2, $orderHeader->addr3, $orderHeader->addr4])) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bagian Kanan: Informasi Dokter & Pemeriksaan -->
                    <div class="p-3">
                        <table class="w-full text-sm border-collapse">
                            <tbody>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap w-40">Dokter Pengirim</td>
                                    <td class="px-2 py-0 w-4">:</td>
                                    <td class="px-2 py-0 text-gray-800 w-full">{{ $orderHeader->clinician }}</td>
                                </tr>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap">Ruangan</td>
                                    <td class="px-2 py-0">:</td>
                                    <td class="px-2 py-0 text-gray-800">{{ $orderHeader->room_desc }}</td>
                                </tr>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap">Tanggal Permintaan</td>
                                    <td class="px-2 py-0">:</td>
                                    <td class="px-2 py-0 text-gray-800">{{ $orderHeader->order_date }}</td>
                                </tr>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap">Tanggal Spesimen</td>
                                    <td class="px-2 py-0">:</td>
                                    <td class="px-2 py-0 text-gray-800">{{ $orderHeader->spl_rcvdt}}</td>
                                </tr>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap">Tanggal Pelaporan</td>
                                    <td class="px-2 py-0">:</td>
                                    <td class="px-2 py-0 text-gray-800">{{ $orderHeader->validate_on }}</td>
                                </tr>
                                <tr class="align-top">
                                    <td class="px-2 py-0 font-medium whitespace-nowrap">Keterangan Klinis</td>
                                    <td class="px-2 py-0">:</td>
                                    <td class="px-2 py-0 text-gray-800">{{ $orderHeader->diag1 }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <hr class="my-4 border-gray-200">

                <!-- Order Details Section -->
                <h6 class="font-bold mb-3 text-center">HASIL PEMERIKSAAN LABORATORIUM</h6>
                <table class="w-full bg-white shadow-md text-sm border-collapse">
                    <thead class="bg-gray-50 text-gray-700 uppercase">
                        <tr>
                            <th class="px-3 py-2 text-left font-bold w-1/4">Nama Pemeriksaan</th>
                            <th class="px-2 py-2 text-left font-bold w-8"></th>
                            <th class="px-3 py-2 text-left font-bold">Hasil</th>
                            <th class="px-3 py-2 text-left font-bold">Satuan</th>
                            <th class="px-3 py-2 text-left font-bold">Nilai Referensi</th>
                            <th class="px-3 py-2 text-left font-bold w-3/10">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($groupedOrderDetails as $groupName => $details)

                        <!-- Group Name Row -->
                        <tr class="bg-gray-100 border-b border-gray-300">
                            <td colspan="6" class="px-3 py-2 text-sm font-semibold text-gray-800">
                                {{ $groupName }}
                            </td>
                        </tr>

                        <!-- Details Rows -->
                        @foreach ($details as $detail)
                        @if ($detail->test_value !== '!' && $detail->test_value !== '.' && $detail->test_value !== '-')
                        <tr class="hover:bg-gray-50 transition duration-150 border-b border-gray-200">
                            <td class="px-3 py-1 {{ $detail->od_item_type === 'P' ? 'pl-6 font-bold' : '' }} {{ $detail->od_item_type === 'U' ? 'pl-10' : '' }}">
                                {{ $detail->test_name }}
                            </td>

                            <td class="px-2 py-1 {{ $detail->abnormal_flag !== 'N' ? 'font-bold text-red-600' : 'text-gray-700' }}">
                                @if ($detail->test_value !== 'Belum Tersedia')
                                @if ($detail->abnormal_flag !== 'N')
                                {{ $detail->abnormal_flag }}
                                @endif
                                @endif
                            </td>



                            @if ($detail->od_data_type == 'W')
                            <td colspan="3" class="px-3 py-1 text-gray-700">
                                {!! nl2br(e($detail->test_value)) !!}
                            </td>
                            @else
                            <td class="px-3 py-1 text-gray-700">
                                @if ($detail->od_data_type !== "X" && $detail->od_data_type !== "P")
                                {{ $detail->test_value }}
                                @endif
                            </td>
                            <td class="px-3 py-1 text-gray-700">
                                @if ($detail->test_value !== "Belum Tersedia")
                                @if ($detail->od_data_type !== "X" && $detail->od_data_type !== "P")
                                {{ $detail->test_unit }}
                                @endif
                                @endif
                            </td>

                            <td class="px-3 py-1 text-gray-700">
                                @if ($detail->ref_range !== 'MRR' && !empty($detail->ref_range))
                                {!! nl2br(e($detail->ref_range)) !!}
                                @endif
                            </td>
                            @endif


                            <td class="px-3 py-1 text-gray-700 font-semibold italic">
                                @if ($detail->test_value !== 'Belum Tersedia')
                                @if ($detail->test_comment)
                                {!! nl2br(e($detail->test_comment)) !!}
                                @endif
                                @if ($detail->attached_comment)
                                {!! nl2br(e($detail->attached_comment)) !!}
                                @endif
                                @endif

                            </td>

                        </tr>

                        @endif
                        @endforeach

                        @endforeach
                    </tbody>

                </table>

            </div>
        </div>
    </div>
</x-app-layout>