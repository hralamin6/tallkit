{{-- Navigation --}}
<nav class="sticky top-0 z-50 backdrop-blur-lg bg-white/80 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            {{-- Logo --}}
            <a wire:navigate href="{{ route('web.home') }}" class="flex items-center space-x-3 group">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition">
                    <span class="text-white font-bold text-xl">💪</span>
                </div>
                <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                    FitHub
                </span>
            </a>

            {{-- Navigation Links --}}
            <div class="hidden md:flex items-center space-x-6">
                <a wire:navigate href="{{ route('web.posts') }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                    Posts
                </a>
                <a wire:navigate href="{{ route('web.categories') }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                    Categories
                </a>
                <a wire:navigate href="{{ route('web.users') }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                    Users
                </a>
            </div>

            {{-- Auth Links --}}
            <div class="flex items-center space-x-4">
                @auth
                    <a wire:navigate href="{{ route('app.dashboard') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                        Dashboard
                    </a>
                @else
                    <a wire:navigate href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a wire:navigate href="{{ route('register') }}" class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-500/50">
                            Get Started
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </div>
</nav>
