<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('img/logo.png') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background: url('{{ asset("img/laboratory-bg.jpg") }}') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            z-index: -1;
        }
        
        .backdrop {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(5px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.8s ease-out;
        }
    </style>
</head>

<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative">
        <div class="text-center">
            <a href="/" class="flex flex-col items-center group">
                <span class="text-4xl font-extrabold text-white animate-fade-in group-hover:scale-105 transition-transform duration-300">
                    ViewsLab
                </span>
                <small class="text-gray-300 text-sm mt-1 animate-fade-in group-hover:text-white transition-colors duration-300">
                    Laboratorium Patologi Klinik, Mikrobiologi Klinik & Patologi Anatomi
                </small>
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-lg backdrop rounded-lg animate-fade-in">
            {{ $slot }}
        </div>
    </div>
</body>

</html>
