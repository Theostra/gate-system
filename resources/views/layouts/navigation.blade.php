<!-- Mobile Sidebar Overlay -->
<div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-20 transition-opacity bg-gray-900 opacity-50 lg:hidden" @click="sidebarOpen = false"></div>

<!-- Sidebar Component -->
<div :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'" class="fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transition duration-300 transform bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 lg:translate-x-0 lg:static lg:inset-auto lg:flex flex-col">
    
    <!-- Header / Logo -->
    <div class="flex items-center h-16 shrink-0 border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-6">
            <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center text-white shadow-sm shadow-indigo-500/20">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <span class="text-lg font-bold text-gray-800 dark:text-white tracking-wide">Gate System</span>
        </a>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-6 overflow-y-auto">
        
        <!-- ================= ROLE KARYAWAN ================= -->
        @if(Auth::user()->role === 'KARYAWAN')
            <!-- UTAMA -->
            <div>
                <p class="px-3 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Utama</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ (request()->routeIs('dashboard') && !request()->routeIs('inbound.*') && !request()->routeIs('outbound.*')) ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Dashboard
                    </a>
                </div>
            </div>

            <!-- AKSES GERBANG -->
            <div>
                <p class="px-3 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Akses Gerbang</p>
                <div class="space-y-1">
                    <a href="{{ route('inbound.create') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('inbound.*') ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Pengajuan Inbound
                    </a>
                    
                    <a href="{{ route('outbound.create') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('outbound.*') ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Pengajuan Outbound
                    </a>
                </div>
            </div>

            <!-- RIWAYAT -->
            <div>
                <p class="px-3 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Laporan</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}#history-section" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Riwayat Pengajuan
                    </a>
                </div>
            </div>
        @endif

        <!-- ================= ROLE GA ================= -->
        @if(Auth::user()->role === 'GA')
            <!-- UTAMA -->
            <div>
                <p class="px-3 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Utama</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Dashboard Portal
                    </a>
                </div>
            </div>

            <!-- OPERASIONAL GA -->
            <div>
                <p class="px-3 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Operasional GA</p>
                <div class="space-y-1">
                    <a href="{{ route('ga.dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('ga.dashboard') ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Validasi Pengajuan
                    </a>
                    
                    <a href="{{ route('ga.history') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('ga.history') ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Histori Validasi
                    </a>
                </div>
            </div>
        @endif

        <!-- ================= ROLE SECURITY ================= -->
        @if(Auth::user()->role === 'SECURITY')
            <!-- UTAMA -->
            <div>
                <p class="px-3 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Utama</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Dashboard Portal
                    </a>
                </div>
            </div>

            <!-- OPERASIONAL GERBANG -->
            <div>
                <p class="px-3 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Pemeriksaan Gerbang</p>
                <div class="space-y-1">
                    <a href="{{ route('security.dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('security.dashboard') ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        Scan Barcode/QR
                    </a>
                    
                    <a href="{{ route('security.history') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('security.history') ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Histori Scan
                    </a>
                </div>
            </div>
        @endif

        <!-- ================= ROLE MANAGEMENT ================= -->
        @if(Auth::user()->role === 'MANAGEMENT')
            <!-- UTAMA -->
            <div>
                <p class="px-3 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Utama</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Dashboard Portal
                    </a>
                </div>
            </div>

            <!-- AUDIT & ANALITIK -->
            <div>
                <p class="px-3 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Audit & Analitik</p>
                <div class="space-y-1">
                    <a href="{{ route('management.dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200 {{ request()->routeIs('management.dashboard') ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Laporan & Analitik
                    </a>
                </div>
            </div>
        @endif

    </nav>

    <!-- Bottom Settings & Profile -->
    <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-transparent">
        
        <!-- Theme Toggle -->
        <div class="flex items-center justify-between px-2 mb-4">
            <x-theme-toggle />
        </div>
        
        <!-- Profile Card -->
        <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-900 border border-gray-200/60 dark:border-gray-700/50 rounded-xl mb-3 shadow-sm">
            <div class="w-9 h-9 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-inner">
                {{ substr(Auth::user()->name, 0, 1) }}
            </div>
            <div class="text-left overflow-hidden">
                <p class="text-sm font-bold text-gray-800 dark:text-gray-200 truncate leading-none mb-1">{{ Auth::user()->name }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium truncate uppercase tracking-wider">{{ Auth::user()->role }} &middot; Profil</p>
            </div>
        </div>

        <!-- Keluar / Logout -->
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 py-2 text-sm font-bold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors bg-red-50 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-950/40 rounded-xl">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Keluar
            </button>
        </form>
    </div>
</div>
