<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Views Laboratory') - SIM Lab RSUD</title>

    <link rel="icon" type="image/ico" href="{{ asset('img/logo.ico') }}">
    <link rel="shortcut icon" href="{{ asset('img/logo.ico') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- DataTables & Select2 Stylesheets -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.tailwindcss.css">
    <link href="https://cdn.datatables.net/buttons/2.3.2/css/buttons.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Global Font & Flat Style Enforcement */
        *, *::before, *::after {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        body {
            background-color: #f8fafc;
            color: #1e293b;
        }

        /* Custom Flat Scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Clean Flat DataTables Refinements */
        .dt-container {
            font-size: 0.8125rem;
        }
        .dt-layout-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .dt-search input {
            font-size: 0.8125rem !important;
            padding: 0.4rem 0.75rem !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.375rem !important;
            background-color: #ffffff !important;
            outline: none !important;
        }
        .dt-search input:focus {
            border-color: #2563eb !important;
            outline: 1px solid #2563eb !important;
        }
        .dt-info {
            font-size: 0.75rem !important;
            color: #64748b !important;
            padding-top: 0.5rem !important;
        }
        .dt-paging .dt-paging-button {
            padding: 0.25rem 0.6rem !important;
            font-size: 0.75rem !important;
            border-radius: 0.25rem !important;
            border: 1px solid #cbd5e1 !important;
            margin: 0 2px !important;
            background: #ffffff !important;
            color: #334155 !important;
        }
        .dt-paging .dt-paging-button.current {
            background: #2563eb !important;
            color: #ffffff !important;
            border-color: #2563eb !important;
            font-weight: 600 !important;
        }
        .dt-paging .dt-paging-button:hover:not(.current):not(.disabled) {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }

        /* Clean Flat Select2 Styling */
        .select2-container {
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single {
            height: 38px !important;
            display: flex !important;
            align-items: center !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.375rem !important;
            background-color: #ffffff !important;
            padding: 0 0.5rem !important;
        }
        .select2-container--default.select2-container--open .select2-selection--single,
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #2563eb !important;
            outline: 1px solid #2563eb !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b !important;
            font-size: 0.8125rem !important;
            font-weight: 500 !important;
            line-height: normal !important;
            padding-left: 0.25rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }
        .select2-dropdown {
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.375rem !important;
            background: #ffffff !important;
            z-index: 9999 !important;
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.25rem !important;
            padding: 0.375rem 0.5rem !important;
            font-size: 0.8125rem !important;
            outline: none !important;
        }
        .select2-results__option {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8125rem !important;
            font-weight: 500 !important;
            color: #334155 !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #2563eb !important;
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        /* Solid Status Badges */
        .badge-status-selesai {
            background-color: #059669 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.75rem !important;
            padding: 0.25rem 0.75rem !important;
            border-radius: 0.25rem !important;
            display: inline-block !important;
            text-decoration: none !important;
            text-align: center !important;
            white-space: nowrap !important;
        }
        .badge-status-selesai:hover {
            background-color: #047857 !important;
        }
        .badge-status-sebagian {
            background-color: #d97706 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.75rem !important;
            padding: 0.25rem 0.75rem !important;
            border-radius: 0.25rem !important;
            display: inline-block !important;
            text-decoration: none !important;
            text-align: center !important;
            white-space: nowrap !important;
        }
        .badge-status-sebagian:hover {
            background-color: #b45309 !important;
        }
        .badge-status-belum {
            background-color: #64748b !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 0.75rem !important;
            padding: 0.25rem 0.75rem !important;
            border-radius: 0.25rem !important;
            display: inline-block !important;
            text-align: center !important;
            white-space: nowrap !important;
        }
        .badge-flag-hh {
            background-color: #e11d48 !important;
            color: #ffffff !important;
            font-weight: 900 !important;
            font-size: 0.6875rem !important;
            padding: 0.15rem 0.5rem !important;
            border-radius: 0.25rem !important;
            display: inline-block !important;
            text-align: center !important;
            letter-spacing: 0.05em !important;
        }
        .badge-flag-ll {
            background-color: #2563eb !important;
            color: #ffffff !important;
            font-weight: 900 !important;
            font-size: 0.6875rem !important;
            padding: 0.15rem 0.5rem !important;
            border-radius: 0.25rem !important;
            display: inline-block !important;
            text-align: center !important;
            letter-spacing: 0.05em !important;
        }

        /* Print Styling */
        @media print {
            nav, header, footer, .no-print, button, form, .dt-search, .dt-paging, .dt-info {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
                font-size: 10.5pt !important;
            }
            .print-only {
                display: block !important;
            }
            .border {
                border-color: #94a3b8 !important;
            }
        }
    </style>
</head>

<body class="h-full font-sans antialiased text-slate-800 bg-slate-100">
    <div class="min-h-screen flex flex-col">
        @include('layouts.navigation')

        <!-- Page Heading (Optional) -->
        @isset($header)
        <header class="bg-white border-b border-slate-200 no-print">
            <div class="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endisset

        <!-- Page Content -->
        <main class="flex-1">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-3 mt-8 text-center text-xs text-slate-500 no-print">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-2">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-700">Views Laboratory</span>
                    <span class="px-1.5 py-0.2 text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-300 rounded">v2.0</span>
                    <span class="text-slate-300">|</span>
                    <span>Sistem Informasi Laboratorium RSUD</span>
                </div>
                <div class="text-slate-400">
                    Terhubung ke Database LIS &bull; {{ date('Y') }}
                </div>
            </div>
        </footer>
    </div>
</body>

</html>