<nav x-data="{ open: false }" class="bg-white border-b border-slate-200 sticky top-0 z-40">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">
            <div class="flex items-center gap-6">
                <!-- Logo & Brand Title -->
                <div class="shrink-0 flex items-center gap-3">
                    <a href="{{ route('klinik.index') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('img/logo.png') }}" alt="RSUD Logo" class="h-9 w-auto object-contain" style="height: 36px; max-height: 36px; width: auto;">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-slate-900 text-sm tracking-tight">Views Lab</span>
                            <span class="px-1.5 py-0.2 text-[10px] font-bold rounded bg-slate-100 text-slate-700 border border-slate-300">v2.0</span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden sm:flex sm:items-center sm:space-x-1 ms-4">
                    <a href="{{ route('klinik.index') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded border transition-colors {{ request()->routeIs('klinik.*') ? 'bg-blue-50 text-blue-800 border-blue-300' : 'text-slate-700 hover:bg-slate-50 border-transparent' }}">
                        {{ __('Patologi Klinik') }}
                    </a>

                    @if (auth()->user()?->id == 1)
                    <a href="{{ route('mikro.index') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded border transition-colors {{ request()->routeIs('mikro.*') ? 'bg-blue-50 text-blue-800 border-blue-300' : 'text-slate-700 hover:bg-slate-50 border-transparent' }}">
                        {{ __('Mikrobiologi Klinik') }}
                    </a>

                    <a href="{{ route('pa.index') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded border transition-colors {{ request()->routeIs('pa.*') ? 'bg-blue-50 text-blue-800 border-blue-300' : 'text-slate-700 hover:bg-slate-50 border-transparent' }}">
                        {{ __('Patologi Anatomi') }}
                    </a>
                    @endif
                    
                    @if(auth()->user()?->id == 2)
                    <a href="{{ route('dashboard.index') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded border transition-colors {{ request()->routeIs('dashboard.*') ? 'bg-blue-50 text-blue-800 border-blue-300' : 'text-slate-700 hover:bg-slate-50 border-transparent' }}">
                        {{ __('Dashboard') }}
                    </a>

                    <a href="{{ route('laporan.index') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded border transition-colors {{ request()->routeIs('laporan.*') ? 'bg-blue-50 text-blue-800 border-blue-300' : 'text-slate-700 hover:bg-slate-50 border-transparent' }}">
                        {{ __('Laporan Laboratorium') }}
                    </a>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown & Status -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <!-- Live Indicator -->
                <div class="mr-4 px-2 py-0.5 rounded bg-emerald-50 border border-emerald-300 text-[11px] font-bold text-emerald-800">
                    Online
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-2.5 py-1.5 border border-slate-300 text-xs font-medium rounded text-slate-800 bg-white hover:bg-slate-50 focus:outline-none">
                            <span class="font-semibold text-slate-800">{{ Auth::user()->name ?? 'Pengguna' }}</span>
                            <span class="text-slate-400 text-[10px]">&#9660;</span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 border-b border-slate-200">
                            <p class="text-xs font-bold text-slate-800">{{ Auth::user()->name ?? 'Pengguna' }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ Auth::user()->email ?? '' }}</p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')" class="text-xs">
                            {{ __('Pengaturan Profil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();" class="text-xs text-red-700 hover:bg-red-50">
                                {{ __('Keluar (Log Out)') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger (Mobile) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded text-slate-600 hover:bg-slate-100 focus:outline-none">
                    <span class="text-xs font-bold">MENU</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (Mobile) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-slate-200 bg-white">
        <div class="pt-2 pb-3 space-y-1 px-2">
            <a href="{{ route('klinik.index') }}" class="block px-3 py-2 rounded text-xs font-medium {{ request()->routeIs('klinik.*') ? 'bg-blue-50 text-blue-800' : 'text-slate-700 hover:bg-slate-50' }}">
                {{ __('Patologi Klinik') }}
            </a>

            @if (auth()->user()?->id == 1)
            <a href="{{ route('mikro.index') }}" class="block px-3 py-2 rounded text-xs font-medium {{ request()->routeIs('mikro.*') ? 'bg-blue-50 text-blue-800' : 'text-slate-700 hover:bg-slate-50' }}">
                {{ __('Mikrobiologi Klinik') }}
            </a>

            <a href="{{ route('pa.index') }}" class="block px-3 py-2 rounded text-xs font-medium {{ request()->routeIs('pa.*') ? 'bg-blue-50 text-blue-800' : 'text-slate-700 hover:bg-slate-50' }}">
                {{ __('Patologi Anatomi') }}
            </a>
            @endif

            @if(auth()->user()?->id == 2)
            <a href="{{ route('dashboard.index') }}" class="block px-3 py-2 rounded text-xs font-medium {{ request()->routeIs('dashboard.*') ? 'bg-blue-50 text-blue-800' : 'text-slate-700 hover:bg-slate-50' }}">
                {{ __('Dashboard') }}
            </a>

            <a href="{{ route('laporan.index') }}" class="block px-3 py-2 rounded text-xs font-medium {{ request()->routeIs('laporan.*') ? 'bg-blue-50 text-blue-800' : 'text-slate-700 hover:bg-slate-50' }}">
                {{ __('Laporan Laboratorium') }}
            </a>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-3 pb-3 border-t border-slate-200 px-4">
            <div class="mb-3">
                <div class="font-bold text-xs text-slate-800">{{ Auth::user()->name ?? 'Pengguna' }}</div>
                <div class="text-[11px] text-slate-500">{{ Auth::user()->email ?? '' }}</div>
            </div>

            <div class="space-y-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50 rounded">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
