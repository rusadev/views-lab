<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Views Laboratory')</title>

    <link rel="icon" type="image/ico" href="{{ asset('img/logo.ico') }}">
    <link rel="shortcut icon" href="{{ asset('img/logo.ico') }}">


    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Scripts -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.tailwindcss.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])


    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com/"></script>
    
    <style>

        .overflow-y-auto {
            max-height: 200px; /* Set a fixed height */
            overflow-y: auto;  /* Enable vertical scrolling */
        }

        
        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        .dt-info {
            font-size: 12px;
        }

        #orderTable th,
        #orderTable td {
            font-size: 0.75rem !important;
        }

        #criticalTable {
            table-layout: fixed;
            width: 100%;
        }

        #criticalTable th:first-child,
        #criticalTable td:first-child {
            width: 30%;
            word-wrap: break-word;
            white-space: normal;

        }

        #criticalTable th:nth-child(2),
        #criticalTable td:nth-child(2) {
            width: 20%;
            word-wrap: break-word;
            white-space: normal;
        }

        #criticalTable td,
        th {
            font-size: 0.75rem !important;
        }

        .dt-search input {
            width: 150px !important;
            height: 30px;
            font-size: 14px;
            padding: 4px;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-selection--single {
            height: 38px !important;
            display: flex !important;
            align-items: center !important;
            border: 1px solid black !important;
            border-radius: 0.3rem !important;
            color: black !important;
            font-weight: 600;
        }
        .select2-container--default .select2-results__option {
            padding: 8px;
            font-size: 14px;
            color: #1E3A8A; /* Warna teks biru tua */
            font-weight: 500;
        }

        /* Efek hover */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: linear-gradient(135deg, #3B82F6, #2563EB); /* Gradasi lebih gelap saat hover */
            color: white;
            font-weight: 600;
            border-radius: 5px;
        }

        input[type="text"],
        input[type="date"] {
            color: black !important;
            font-size: 14px;
            font-weight: 600; 
        }
        input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
        }
        input:focus {
            background: white !important;
            color: black !important;
            font-weight: 600;
            border: 2px solid #3B82F6;
        }

        /* Label styling */
        label {
            font-size: 14px;
            font-weight: 600;
            color: #1E3A8A;
        }


    
        .animate-pulse {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                background-color: #E5E7EB;
            }

            50% {
                background-color: #D1D5DB;
            }

            100% {
                background-color: #E5E7EB;
            }
        }

        @keyframes pulse-fast {
            0% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
            100% {
                opacity: 1;
            }
        }

        .animate-pulse-fast {
            animation: pulse-fast 0.5s ease-in-out infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        

        
    </style>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>