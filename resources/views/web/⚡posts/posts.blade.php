<div>
  {{-- Header --}}
  <section class="relative overflow-hidden py-12 lg:py-16 bg-gradient-to-br from-indigo-500/10 via-purple-500/10 to-pink-500/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-8">
        <h1 class="text-4xl md:text-5xl font-extrabold mb-4">
          <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
            Fitness & Health Articles
          </span>
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
          Discover expert advice, workout tips, nutrition guides, and motivation to transform your life
        </p>
        
        {{-- Stats --}}
        <div class="flex justify-center gap-8 mt-6">
          <div class="text-center">
            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($this->stats['total']) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Articles</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($this->stats['categories']) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Categories</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Main Content --}}
  <section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      {{-- Search & Filters --}}
      <div class="mb-8 space-y-4">
        {{-- Search Bar --}}
        <div class="relative">
          <input 
            type="text" 
            wire:model.live.debounce.500ms="search"
            placeholder="Search articles..."
            class="w-full px-6 py-4 pl-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition shadow-lg"
          >
          <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          @if($search)
            <button 
              wire:click="$set('search', '')"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          @endif
        </div>

        {{-- Filter Bar --}}
        <div class="flex flex-wrap gap-4 items-center justify-between">
          {{-- Category Filters --}}
          <div class="flex flex-wrap gap-2">
            <button 
              wire:click="$set('category', null)"
              @class([
                'px-4 py-2 rounded-lg text-sm font-medium transition',
                'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/50' => !$category,
                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-indigo-500' => $category,
              ])
            >
              All Topics
            </button>
            
            @foreach($this->categories->take(6) as $cat)
              <button 
                wire:click="$set('category', '{{ $cat->id }}')"
                @class([
                  'px-4 py-2 rounded-lg text-sm font-medium transition',
                  'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/50' => $category === $cat->id,
                  'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-indigo-500' => $category !== $cat->id,
                ])
              >
                {{ $cat->name }} ({{ $cat->posts_count }})
              </button>
            @endforeach

            @if($this->categories->count() > 6)
              <button 
                wire:click="$toggle('showFilters')"
                class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 transition"
              >
                {{ $showFilters ? 'Less' : 'More' }} Categories
              </button>
            @endif
          </div>

          {{-- Sort Options --}}
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Sort:</span>
            <select 
              wire:model.live="sortBy"
              class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            >
              <option value="latest">Latest</option>
              <option value="popular">Most Popular</option>
              <option value="oldest">Oldest</option>
            </select>
          </div>
        </div>

        {{-- More Categories (Expandable) --}}
        @if($showFilters && $this->categories->count() > 6)
          <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
            @foreach($this->categories->skip(6) as $cat)
              <button 
                wire:click="$set('category', '{{ $cat->id }}')"
                @class([
                  'px-4 py-2 rounded-lg text-sm font-medium transition',
                  'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/50' => $category === $cat->id,
                  'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-indigo-500' => $category !== $cat->id,
                ])
              >
                {{ $cat->name }} ({{ $cat->posts_count }})
              </button>
            @endforeach
          </div>
        @endif

        {{-- Active Filters --}}
        @if($search || $category)
          <div class="flex items-center gap-2 pt-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Active filters:</span>
            @if($search)
              <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm">
                Search: "{{ Str::limit($search, 20) }}"
                <button wire:click="$set('search', '')" class="hover:text-indigo-900 dark:hover:text-indigo-100">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </span>
            @endif
            @if($category)
              @php
                $selectedCat = $this->categories->firstWhere('id', $category);
              @endphp
              @if($selectedCat)
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full text-sm">
                  {{ $selectedCat->name }}
                  <button wire:click="$set('category', null)" class="hover:text-purple-900 dark:hover:text-purple-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </span>
              @endif
            @endif
            <button 
              wire:click="resetFilters"
              class="text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 underline"
            >
              Clear all
            </button>
          </div>
        @endif
      </div>

      {{-- Posts Grid --}}
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        @forelse($this->posts as $post)
          <article 
            wire:key="post-{{ $post->id }}"
            class="group bg-white dark:bg-gray-800 rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-500 transition-all duration-300 hover:shadow-2xl hover:shadow-indigo-500/20 hover:-translate-y-2"
          >
            <a wire:navigate href="{{ route('web.post', $post->slug) }}" class="block">
            {{-- Post Image --}}
            <div class="relative h-48 bg-gradient-to-br from-indigo-500 to-purple-600 overflow-hidden">
              @if($post->getFirstMediaUrl('featured_image'))
                <img 
                  src="{{ $post->getFirstMediaUrl('featured_image') }}" 
                  alt="{{ $post->title }}" 
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
              
              {{-- Category Badge --}}
              @if($post->category)
                <div class="absolute top-4 left-4">
                  <span class="px-3 py-1 text-xs font-medium text-white bg-white/20 backdrop-blur-sm rounded-full border border-white/30">
                    {{ $post->category->name }}
                  </span>
                </div>
              @endif
            </div>

            {{-- Post Content --}}
            <div class="p-6">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3 line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                {{ $post->title }}
              </h3>
              
              <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3">
                {{ $post->excerpt }}
              </p>

              {{-- Author & Meta --}}
              <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-2">
                  <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold">
                    {{ substr($post->user->name ?? 'A', 0, 1) }}
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ Str::limit($post->user->name ?? 'Anonymous', 20) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $post->published_at?->diffForHumans() }}</p>
                  </div>
                </div>
                
                <div class="flex items-center space-x-3 text-gray-500 dark:text-gray-400">
                  <span class="text-xs flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{ number_format($post->views_count) }}
                  </span>
                </div>
              </div>
            </div>
            </a>
          </article>
        @empty
          <div class="col-span-full text-center py-16">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No articles found</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              @if($search || $category)
                Try adjusting your filters or search terms
              @else
                Check back soon for new content!
              @endif
            </p>
            @if($search || $category)
              <button 
                wire:click="resetFilters"
                class="px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-500/50"
              >
                Clear Filters
              </button>
            @endif
          </div>
        @endforelse
      </div>

      {{-- Pagination --}}
      @if($this->posts->hasPages())
        <div class="flex justify-center">
          {{ $this->posts->links() }}
        </div>
      @endif
    </div>
  </section>

</div>