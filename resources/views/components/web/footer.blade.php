{{-- Footer --}}
<footer class="bg-gray-900 text-gray-400 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-8 mb-8">
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold">💪</span>
                    </div>
                    <span class="text-xl font-bold text-white">FitHub</span>
                </div>
                <p class="text-sm">
                    Your ultimate fitness and health community platform.
                </p>
            </div>
            
            <div>
                <h3 class="text-white font-semibold mb-4">Platform</h3>
                <ul class="space-y-2 text-sm">
                    <li><a wire:navigate href="{{ route('web.home') }}" class="hover:text-white transition">Home</a></li>
                    <li><a wire:navigate href="{{ route('web.posts') }}" class="hover:text-white transition">Posts</a></li>
                    <li><a wire:navigate href="{{ route('web.users') }}" class="hover:text-white transition">Community</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-white font-semibold mb-4">Resources</h3>
                <ul class="space-y-2 text-sm">
                    <li><a wire:navigate href="{{ route('web.posts') }}" class="hover:text-white transition">Blog</a></li>
                    <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                    <li><a href="#" class="hover:text-white transition">Community</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-white font-semibold mb-4">Legal</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-white transition">Terms of Service</a></li>
                    <li><a href="#" class="hover:text-white transition">Cookie Policy</a></li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-800 pt-8 text-center text-sm">
            <p>&copy; {{ date('Y') }} FitHub. All rights reserved. Built with Laravel & Livewire.</p>
        </div>
    </div>
</footer>
