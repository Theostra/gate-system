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

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>
    </head>
    <body class="font-sans antialiased">
        <div class="flex h-screen bg-gray-100 dark:bg-gray-900 overflow-hidden" x-data="{ sidebarOpen: false }">
            
            <!-- Sidebar -->
            @include('layouts.navigation')

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
                <!-- Mobile Header -->
                <header class="bg-white dark:bg-gray-800 shadow-sm lg:hidden h-16 shrink-0 flex items-center px-4 z-10 border-b border-gray-200 dark:border-gray-700 gap-4">
                    <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="flex items-center">
                        <img src="{{ asset('images/biofarma-logo.png') }}" alt="Bio Farma" class="h-8 w-auto">
                    </div>
                </header>

                <!-- Scrollable Main Content -->
                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
                    <!-- Page Heading -->
                    @isset($header)
                        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </div>
                    @endisset
                    
                    <!-- Content Slot with Padding -->
                    <div class="w-full h-full p-4 sm:p-6 lg:p-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <!-- Global Toast Notification Component -->
        <div
            x-data="{ 
                show: false, 
                message: '', 
                type: 'success',
                init() {
                    @if(session()->has('message'))
                        this.message = '{{ session('message') }}';
                        this.type = 'success';
                        this.show = true;
                        setTimeout(() => this.show = false, 3000);
                    @elseif(session()->has('error'))
                        this.message = '{{ session('error') }}';
                        this.type = 'error';
                        this.show = true;
                        setTimeout(() => this.show = false, 5000);
                    @endif

                    window.addEventListener('toast', event => {
                        let detail = event.detail[0] || event.detail;
                        this.message = detail.message;
                        this.type = detail.type || 'success';
                        this.show = true;
                        setTimeout(() => this.show = false, 3000);
                    });
                }
            }"
            x-show="show"
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed bottom-0 right-0 mb-8 mr-8 z-50 max-w-sm w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
            style="display: none;"
        >
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <!-- Success Icon -->
                        <svg x-show="type === 'success'" class="h-6 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Error Icon -->
                        <svg x-show="type === 'error'" class="h-6 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Warning Icon -->
                        <svg x-show="type === 'warning'" class="h-6 w-6 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p x-text="message" class="text-sm font-medium text-gray-900 dark:text-gray-100"></p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="show = false" class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
