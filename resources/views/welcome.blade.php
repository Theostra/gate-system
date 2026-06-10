<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gate System Enterprise</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAFC;
        }
        .bg-corporate-blue {
            background-color: #0F2A4A;
        }
        .text-corporate-blue {
            color: #0F2A4A;
        }
        /* Sliding Background Animation */
        .sliding-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 200%; /* Wider than screen for sliding effect */
            height: 100vh;
            background-image: url('{{ asset("images/biofarma-bg.jpg") }}');
            background-size: cover;
            background-position: center;
            background-repeat: repeat-x;
            opacity: 0.15;
            z-index: -1;
            animation: slideLeft 60s linear infinite;
        }
        @keyframes slideLeft {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col font-sans relative">
    
    <!-- Sliding Background -->
    <div class="sliding-background pointer-events-none"></div>

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-20">
                <div class="flex items-center space-x-8">
                    <div class="flex-shrink-0 flex items-center">
                        <img src="{{ asset('images/biofarma-logo.png') }}" alt="Bio Farma Logo" class="h-10 w-auto">
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <x-theme-toggle />
                        
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="inline-flex items-center px-5 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-corporate-blue hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-900 transition-colors">
                                    Dashboard Portal
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">Masuk</a>
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="flex-grow flex flex-col justify-center bg-transparent relative z-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <h1 class="text-3xl tracking-tight font-extrabold text-gray-900 sm:text-4xl md:text-5xl mb-4">
                Selamat Datang di
            </h1>
            <h2 class="text-2xl tracking-tight font-extrabold text-corporate-blue sm:text-3xl md:text-4xl mb-4">
                PT Bio Farma (Persero)
            </h2>
            
            <div class="mt-8 mx-auto w-full max-w-md">
                @auth
                    <a href="{{ url('/dashboard') }}" class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-lg text-lg font-bold text-white bg-corporate-blue hover:bg-blue-900 transition-colors">
                        Lanjutkan ke Dashboard Portal
                    </a>
                @else
                    <form method="POST" action="{{ route('login') }}" class="bg-white/90 backdrop-blur-md p-6 sm:p-8 rounded-2xl shadow-2xl text-left border border-white/50 relative overflow-hidden">
                        @csrf
                        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-corporate-blue to-blue-500"></div>
                        
                        <div class="mb-5">
                            <label for="email" class="block text-sm font-semibold text-corporate-blue mb-2">Alamat Email</label>
                            <input id="email" type="email" name="email" required autofocus class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-corporate-blue focus:border-corporate-blue transition-colors bg-white" placeholder="nama@biofarma.co.id">
                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-xs" />
                        </div>
                        
                        <div class="mb-6">
                            <label for="password" class="block text-sm font-semibold text-corporate-blue mb-2">Kata Sandi</label>
                            <input id="password" type="password" name="password" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-corporate-blue focus:border-corporate-blue transition-colors bg-white" placeholder="••••••••">
                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-xs" />
                        </div>

                        <div class="flex items-center justify-between mb-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="remember" class="rounded border-gray-300 text-corporate-blue focus:ring-corporate-blue">
                                <span class="ml-2 text-xs text-gray-600 font-medium">Ingat Sesi Ini</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-corporate-blue hover:text-blue-800 transition-colors">Lupa Sandi?</a>
                            @endif
                        </div>

                        <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-corporate-blue hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-corporate-blue transition-all transform hover:-translate-y-0.5">
                            MASUK PORTAL
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center">
            <p class="text-sm text-gray-500">
                &copy; {{ date('Y') }} Gate System Enterprise. All rights reserved.
            </p>
            <div class="mt-4 md:mt-0 flex space-x-6 text-sm text-gray-500">
                <span>Versi 1.2.0</span>
                <span>Hubungi IT Support</span>
            </div>
        </div>
    </footer>
</body>
</html>
