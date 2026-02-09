@section('title', $this->post->title)
@section('description', Str::limit(strip_tags($this->post->content), 333))
@section('image', $this->post->getFirstMediaUrl('featured_image'))

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-950">
  
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

        {{-- Auth Links --}}
        <div class="flex items-center space-x-4">
          <a wire:navigate href="{{ route('web.posts') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
            All Articles
          </a>
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

  {{-- Article Content --}}
  <article class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      
      {{-- Breadcrumb --}}
      <nav class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400 mb-8">
        <a wire:navigate href="{{ route('web.home') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Home</a>
        <span>/</span>
        <a wire:navigate href="{{ route('web.posts') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Articles</a>
        @if($this->post->category)
          <span>/</span>
          <span class="text-gray-900 dark:text-white font-medium">{{ $this->post->category->name }}</span>
        @endif
      </nav>

      {{-- Article Header --}}
      <header class="mb-8">
        {{-- Category Badge --}}
        @if($this->post->category)
          <div class="mb-4">
            <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-medium rounded-full">
              {{ $this->post->category->name }}
            </span>
          </div>
        @endif

        {{-- Title --}}
        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white mb-6 leading-tight">
          {{ $this->post->title }}
        </h1>

        {{-- Meta Info --}}
        <div class="flex flex-wrap items-center gap-6 text-gray-600 dark:text-gray-400 mb-6">
          {{-- Author --}}
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-lg font-bold">
              {{ substr($this->post->user->name ?? 'A', 0, 1) }}
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $this->post->user->name ?? 'Anonymous' }}</p>
              <p class="text-xs">{{ $this->post->published_at?->format('M d, Y') }}</p>
            </div>
          </div>

          {{-- Stats --}}
          <div class="flex items-center space-x-4 text-sm">
            <span class="flex items-center">
              <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              {{ number_format($this->post->views_count) }} views
            </span>
            <span class="flex items-center">
              <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ ceil(str_word_count(strip_tags($this->post->content)) / 200) }} min read
            </span>
          </div>
        </div>

        {{-- Share Button --}}
        <div x-data="{open:false}" class="relative inline-block">
          <button 
            @click="open=!open" 
            @keydown.escape.window="open=false" 
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition" 
            aria-haspopup="true" 
            :aria-expanded="open ? 'true' : 'false'"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 8a3 3 0 1 0-6 0 3 3 0 0 0 6 0zM18 20a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM6 20a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
            </svg>
            <span>Share Article</span>
          </button>
          
          <div 
            x-cloak 
            x-show="open" 
            x-transition.opacity 
            @click.outside="open=false" 
            class="absolute left-0 mt-2 w-72 p-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xl z-20"
          >
            <div class="grid grid-cols-2 gap-2 text-xs">
              <a href="https://www.facebook.com/sharer/sharer.php?u={{ $this->shareUrl }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 text-blue-600 dark:text-blue-400 inline-flex items-center gap-2 transition">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Facebook
              </a>
              <a href="https://x.com/intent/tweet?url={{ $this->shareUrl }}&text={{ $this->shareText }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-2 transition">
                <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> X (Twitter)
              </a>
              <a href="https://wa.me/?text={{ $this->shareText }}%20{{ $this->shareUrl }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-2 transition">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> WhatsApp
              </a>
              <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $this->shareUrl }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg hover:bg-sky-50 dark:hover:bg-sky-900/20 text-sky-600 dark:text-sky-400 inline-flex items-center gap-2 transition">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> LinkedIn
              </a>
              <a href="https://www.reddit.com/submit?url={{ $this->shareUrl }}&title={{ $this->shareText }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-900/20 text-orange-600 dark:text-orange-400 inline-flex items-center gap-2 transition">
                <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> Reddit
              </a>
              <a href="https://t.me/share/url?url={{ $this->shareUrl }}&text={{ $this->shareText }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg hover:bg-cyan-50 dark:hover:bg-cyan-900/20 text-cyan-600 dark:text-cyan-400 inline-flex items-center gap-2 transition">
                <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span> Telegram
              </a>
              <a href="mailto:?subject={{ $this->shareText }}&body={{ $this->shareText }}%0A%0A{{ $this->shareUrl }}" class="px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-2 transition">
                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Email
              </a>
              <a href="https://www.pinterest.com/pin/create/button/?url={{ $this->shareUrl }}&description={{ $this->shareText }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 inline-flex items-center gap-2 transition">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Pinterest
              </a>
              <button 
                type="button"
                @click="
                  const title = decodeURIComponent('{{ $this->shareText }}');
                  const url = decodeURIComponent('{{ $this->shareUrl }}');
                  if (navigator.share) {
                    navigator.share({ title, text: title, url }).catch(()=>{});
                  } else {
                    alert('Sharing not supported on this browser.');
                  }
                "
                class="px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-2 col-span-2 transition"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> Device Share
              </button>
            </div>
          </div>
        </div>
      </header>

      {{-- Featured Image --}}
      @if($this->post->getFirstMediaUrl('featured_image'))
        <div class="mb-12 rounded-2xl overflow-hidden shadow-2xl">
          <img 
            src="{{ $this->post->getFirstMediaUrl('featured_image') }}" 
            alt="{{ $this->post->title }}"
            class="w-full h-auto"
          >
        </div>
      @endif

      {{-- Article Content --}}
      <div class="prose prose-lg prose-indigo dark:prose-invert max-w-none mb-12">
        <div class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm rounded-2xl p-8 border border-gray-200 dark:border-gray-700">
          {!! Str::markdown($this->post->content) !!}
        </div>
      </div>

      {{-- Tags/Keywords (if available) --}}
      @if($this->post->meta_keywords)
        <div class="mb-8">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Tags</h3>
          <div class="flex flex-wrap gap-2">
            @foreach(explode(',', $this->post->meta_keywords) as $keyword)
              <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-xs">
                {{ trim($keyword) }}
              </span>
            @endforeach
          </div>
        </div>
      @endif

      {{-- Share Section --}}
      <div class="flex items-center justify-between py-8 border-y border-gray-200 dark:border-gray-700 mb-12">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Enjoyed this article?</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">Share it with your friends and help spread the knowledge!</p>
        </div>
      </div>

      {{-- Author Bio --}}
      <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-8 border border-indigo-200 dark:border-gray-700 mb-12">
        <div class="flex items-start space-x-4">
          <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">
            {{ substr($this->post->user->name ?? 'A', 0, 1) }}
          </div>
          <div class="flex-1">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $this->post->user->name ?? 'Anonymous' }}</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
              Fitness enthusiast and health advocate sharing knowledge and inspiration with the community.
            </p>
            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
              <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                {{ $this->post->user->posts_count ?? 0 }} articles
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </article>

  {{-- Related Posts --}}
  @if($this->relatedPosts->isNotEmpty())
    <section class="py-16 bg-white/50 dark:bg-gray-900/50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Related Articles</h2>
        
        <div class="grid md:grid-cols-3 gap-8">
          @foreach($this->relatedPosts as $relatedPost)
            <article 
              class="group bg-white dark:bg-gray-800 rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-500 transition-all duration-300 hover:shadow-2xl hover:shadow-indigo-500/20 hover:-translate-y-2 cursor-pointer"
              onclick="window.location.href='{{ route('web.post', $relatedPost->slug) }}'"
            >
              {{-- Post Image --}}
              <div class="relative h-48 bg-gradient-to-br from-indigo-500 to-purple-600 overflow-hidden">
                @if($relatedPost->getFirstMediaUrl('featured_image'))
                  <img 
                    src="{{ $relatedPost->getFirstMediaUrl('featured_image') }}" 
                    alt="{{ $relatedPost->title }}" 
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                    loading="lazy"
                  >
                @else
                  <div class="w-full h-full flex items-center justify-center text-white text-6xl">
                    @php
                      $emojis = ['💪', '🏃', '🧘', '🥗', '🏋️', '🥇', '❤️', '🌟'];
                      echo $emojis[array_rand($emojis)];
                    @endphp
                  </div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
              </div>

              {{-- Post Content --}}
              <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                  {{ $relatedPost->title }}
                </h3>
                
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                  {{ $relatedPost->excerpt }}
                </p>

                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                  <span>{{ $relatedPost->published_at?->diffForHumans() }}</span>
                  <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{ number_format($relatedPost->views_count) }}
                  </span>
                </div>
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </section>
  @endif

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
            <li><a wire:navigate href="{{ route('web.posts') }}" class="hover:text-white transition">Articles</a></li>
            <li><a href="#" class="hover:text-white transition">About Us</a></li>
          </ul>
        </div>
        
        <div>
          <h3 class="text-white font-semibold mb-4">Resources</h3>
          <ul class="space-y-2 text-sm">
            <li><a href="#" class="hover:text-white transition">Help Center</a></li>
            <li><a href="#" class="hover:text-white transition">Community</a></li>
          </ul>
        </div>
        
        <div>
          <h3 class="text-white font-semibold mb-4">Legal</h3>
          <ul class="space-y-2 text-sm">
            <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
            <li><a href="#" class="hover:text-white transition">Terms of Service</a></li>
          </ul>
        </div>
      </div>
      
      <div class="border-t border-gray-800 pt-8 text-center text-sm">
        <p>&copy; {{ date('Y') }} FitHub. All rights reserved. Built with Laravel & Livewire.</p>
      </div>
    </div>
  </footer>
</div>
