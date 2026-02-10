<?php

namespace App\Services\BotBook;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\AiServiceFactory;
use App\Services\BotBook\StructuredResponse;
use Illuminate\Support\Str;

class PostGeneratorService
{
    /**
     * Post content types with their characteristics
     */
    private array $contentTypes = [

    'self_sufficiency' => [
        'title_prompt' => 'গ্রামীণ জীবন, স্বয়ংসম্পূর্ণতা, আত্মনির্ভরশীল জীবনযাপন, হোমস্টেডিং, অফ-গ্রিড লাইফস্টাইল, শহর বনাম গ্রাম, প্রাকৃতিক জীবন, টেকসই বসবাস',
        'overview' => 'এই সেকশনটি গ্রামীণ ও স্বয়ংসম্পূর্ণ জীবনধারার দর্শন তুলে ধরে। এখানে কম ভোগে স্বাধীনতা, নিজের খাবার নিজে উৎপাদন, প্রকৃতির সাথে সংযোগ এবং শহুরে জীবনের বিকল্প পথের কথা বলা হয়। উপরের কীওয়ার্ডগুলোর মধ্য থেকে একটি নির্বাচন করে সেই বিষয়ের উপর ইতিবাচক ও বাস্তবসম্মত একটি পোস্ট লিখুন।',
    ],

    'captain_green_mindset' => [
        'title_prompt' => 'ক্যাপ্টেন গ্রিন মাইন্ডসেট, শৃঙ্খলা, ডিসিপ্লিন, রুটিন, নেতৃত্ব, মানসিক শক্তি, মিশন-ভিত্তিক জীবনযাপন',
        'overview' => 'এই সেকশনটি ক্যাপ্টেন গ্রিন পারসোনার মূল ভাবনা তুলে ধরে। এখানে শৃঙ্খলা, আত্মনিয়ন্ত্রণ, দায়িত্ববোধ এবং মিশন-ভিত্তিক জীবনদর্শনের উপর জোর দেওয়া হয়। উপরের কীওয়ার্ডগুলোর যেকোনো একটি বেছে নিয়ে অনুপ্রেরণামূলক ও বাস্তবধর্মী পোস্ট লিখুন।',
    ],

    'animal_based_nutrition' => [
        'title_prompt' => 'প্রাণীজ পুষ্টি, প্রাণীজ প্রোটিন, স্বাস্থ্যকর চর্বি, রেড মিট, প্রোটিন ইনভেস্টমেন্ট, শক্তি ও ভাইটালিটি',
        'overview' => 'এই সেকশনটি প্রাণীজ খাদ্যভিত্তিক পুষ্টি দর্শনের উপর ভিত্তি করে। এখানে প্রোটিন ও ফ্যাটকে শক্তি, সুস্থতা এবং দীর্ঘমেয়াদি স্বাস্থ্যের দৃষ্টিকোণ থেকে আলোচনা করা হয়। উপরের কীওয়ার্ড থেকে একটি নির্বাচন করে তথ্যবহুল ও ব্যালেন্সড পোস্ট লিখুন।',
    ],

    'plant_toxin_awareness' => [
        'title_prompt' => 'উদ্ভিদের বিষ, লেক্টিন, অক্সালেট, ফাইটেট, গ্লুটেন, কাঁচা শাকসবজি, গ্রিন স্মুথি, গাট হেলথ',
        'overview' => 'এই সেকশনটি উদ্ভিদের প্রাকৃতিক প্রতিরক্ষা ব্যবস্থা ও সম্ভাব্য খাদ্য-সংবেদনশীলতা নিয়ে সচেতনতা তৈরির জন্য। এখানে ভয় নয়, বরং বোঝাপড়া ও ব্যক্তিভেদে খাদ্য নির্বাচনের গুরুত্ব তুলে ধরা হয়। উপরের কীওয়ার্ডগুলোর একটি নিয়ে ব্যাখ্যামূলক পোস্ট লিখুন।',
    ],

    'fermentation_tradition' => [
        'title_prompt' => 'ফারমেন্টেশন, ল্যাক্টো-ফারমেন্টেড খাবার, কেফির, আচারের বিজ্ঞান, অঙ্কুরোদগম, প্রোবায়োটিক',
        'overview' => 'এই সেকশনটি ঐতিহ্যবাহী খাদ্য প্রক্রিয়াজাতকরণ পদ্ধতি এবং তাদের উপকারিতা নিয়ে। দাদী-নানীদের জ্ঞান ও আধুনিক পুষ্টি বিজ্ঞানের সংযোগ এখানে মূল ভাবনা। উপরের কীওয়ার্ড থেকে একটি বেছে নিয়ে ব্যবহারিক ও সহজ ভাষায় পোস্ট লিখুন।',
    ],

    'regenerative_agro' => [
        'title_prompt' => 'খেসারি ডাল, বিশেষ শস্য, নাইট্রোজেন ফিক্সেশন, মাটির উর্বরতা, রিজেনারেটিভ কৃষি, লোকাল ফসল',
        'overview' => 'এই সেকশনটি টেকসই কৃষি, মাটির স্বাস্থ্য এবং স্থানীয় শস্য ব্যবস্থার উপর কেন্দ্রীভূত। এখানে খাদ্য নিরাপত্তা ও পরিবেশবান্ধব কৃষির গুরুত্ব তুলে ধরা হয়। উপরের কীওয়ার্ডগুলোর একটি নির্বাচন করে শিক্ষামূলক পোস্ট লিখুন।',
    ],

    'ethical_products' => [
        'title_prompt' => 'ক্লিন ফুড, নিরাপদ খাবার, কেফির, সরিষার তেল, খাঁটি খাবার চেনার উপায়, নৈতিক ব্যবসা',
        'overview' => 'এই সেকশনটি নিরাপদ ও খাঁটি খাবার, নৈতিক ব্যবসা এবং গ্রাম থেকে শহরে খাবার পৌঁছানোর দর্শন তুলে ধরে। উপরের কীওয়ার্ডগুলোর একটি নিয়ে বিশ্বাসযোগ্য ও স্বচ্ছ দৃষ্টিভঙ্গিতে পোস্ট লিখুন।',
    ],

    'prepping_survival' => [
        'title_prompt' => 'সারভাইভালিজম, প্রিপিং, সংকট প্রস্তুতি, খাবার মজুদ, অফ-গ্রিড দক্ষতা, মানসিক দৃঢ়তা',
        'overview' => 'এই সেকশনটি সংকটকালীন প্রস্তুতি এবং আত্মনির্ভরশীল দক্ষতার গুরুত্ব বোঝাতে তৈরি। এখানে ভয় নয়, বরং সচেতন প্রস্তুতির ধারণা দেওয়া হয়। উপরের কীওয়ার্ডগুলোর একটি বেছে নিয়ে বাস্তবধর্মী পোস্ট লিখুন।',
    ],

    'eco_lifestyle' => [
        'title_prompt' => 'পরিবেশবান্ধব জীবনযাপন, CEB ঘর নির্মাণ, ইকো হাউসিং, গ্রাউন্ডিং, স্বাস্থ্যকর অভ্যাস',
        'overview' => 'এই সেকশনটি পরিবেশবান্ধব অবকাঠামো ও প্রাকৃতিক জীবনযাত্রার অভ্যাস নিয়ে। এখানে প্রকৃতির সাথে সামঞ্জস্যপূর্ণ সুস্থ জীবনধারার কথা বলা হয়। উপরের কীওয়ার্ড থেকে একটি নির্বাচন করে ব্যাখ্যামূলক পোস্ট লিখুন।',
    ],

    'anti_consumerism' => [
        'title_prompt' => 'ভোক্তাবাদ বিরোধীতা, সস্তা খাবারের ফাঁদ, ফেইক উন্নয়ন, বাস্তব উন্নয়ন, সচেতন জীবনযাপন',
        'overview' => 'এই সেকশনটি আধুনিক ভোক্তাবাদ ও ভুয়া উন্নয়নের সমালোচনা করে, পাশাপাশি বাস্তব ও টেকসই বিকল্প দেখায়। উপরের কীওয়ার্ডগুলোর একটি নিয়ে চিন্তাশীল ও সমাধানমুখী পোস্ট লিখুন।',
    ],

];


    /**
     * Generate multiple posts
     */
    public function generatePosts(int $count = 5): array
    {
        $generatedPosts = [];

        for ($i = 0; $i < $count; $i++) {
            try {
                $post = $this->generateSinglePost();
                
                if ($post) {
                    $generatedPosts[] = $post;
                    
                    \Log::info('Post created', [
                        'id' => $post->id,
                        'title' => $post->title,
                        'author' => $post->user->name,
                    ]);
                }

                // Delay to avoid rate limiting
                sleep(3);
            } catch (\Exception $e) {
                \Log::error('Post generation failed', [
                    'iteration' => $i,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $generatedPosts;
    }

    /**
     * Generate a single post
     */
    private function generateSinglePost(): ?Post
    {
        // 1. Select random content type
        $contentType = array_rand($this->contentTypes);
        $typeConfig = $this->contentTypes[$contentType];

        // 2. Find best bot user (least posts + personality match)
        $botUser = $this->selectBotUser();
        
        if (!$botUser) {
            \Log::warning('No suitable bot user found');
            return null;
        }

        // 3. Generate post content with AI (using Gemini for better long-form content)
        $aiService = AiServiceFactory::make('cerebras');
        $postData = $this->generatePostContent($aiService, $typeConfig);
        
        if (!$postData) {
            \Log::warning('AI post content generation failed');
            return null;
        }

        // 4. Select appropriate category
        $category = $this->selectAppropriateCategory($postData['title'], $contentType);

        // 5. Generate SEO meta fields
        $seoData = $this->generateSEOFields($aiService, $postData['title'], $postData['excerpt']);

        // 6. Create post
        $post = Post::create([
            'user_id' => $botUser->id,
            'category_id' => $category?->id,
            'title' => $postData['title'],
            'slug' => Str::slug($postData['title']) . '-' . Str::random(6),
            'excerpt' => $postData['excerpt'],
            'content' => $postData['content'],
            'is_featured' => false,
            'published_at' => now(),
            'views_count' => 0,
            'meta_title' => $seoData['meta_title'] ?? null,
            'meta_description' => $seoData['meta_description'] ?? null,
            'meta_keywords' => $seoData['meta_keywords'] ?? null,
        ]);

        // 7. Generate and attach featured image
        try {
            $this->generateFeaturedImage($post, $postData);
        } catch (\Exception $e) {
            \Log::warning('Featured image generation failed', [
                'post_id' => $post->id,
                'error' => $e->getMessage()
            ]);
        }

        return $post;
    }

    /**
     * Select bot user with least posts and matching personality
     */
    private function selectBotUser(): ?User
    {
        // Get bot users with post counts
        $botUsers = User::whereHas('roles', function ($q) {
            $q->where('name', 'user');
        })
        ->whereHas('detail') // Ensure they have details
        ->withCount('posts')
        ->get();

        if ($botUsers->isEmpty()) {
            return null;
        }

        // Filter by preferred personalities if possible
        // For now, just get the one with least posts
        return $botUsers->sortBy('posts_count')->first();
    }

    /**
     * Generate post content using AI
     */
    private function generatePostContent($aiService, array $typeConfig, $model=null): ?array
    {
        $prompt = "{$typeConfig['overview']}. '{$typeConfig['title_prompt']}' সম্পর্কে একটি বিস্তারিত এবং আকর্ষণীয় ব্লগ পোস্ট লিখুন। "
            . "শর্তাবলী:\n"
            . "- দৈর্ঘ্য: ১০০০-১৫০০ শব্দ (সংক্ষিপ্ত কিন্তু তথ্যবহুল রাখুন)\n"
            . "- যথাযথ স্থানে বুলেট পয়েন্ট এবং সংখ্যায়িত তালিকা ব্যবহার করুন\n"
            . "- মূল পয়েন্টগুলোতে জোর দেওয়ার জন্য **বোল্ড** ব্যবহার করুন\n"
            . "- সূক্ষ্ম গুরুত্ব বোঝাতে *ইটালিক* ব্যবহার করুন\n"
            . "- ব্যস্ততা বাড়াতে প্রাসঙ্গিক ইমোজি (💪, 🏃, 🥗, ইত্যাদি) পরিমিতভাবে ব্যবহার করুন\n"
            . "- কোনো টেবিল বা জটিল ফরম্যাটিং ব্যবহার করবেন না\n"
            . "- শেষে একটি সংক্ষিপ্ত কল-টু-অ্যাকশন অন্তর্ভুক্ত করুন\n"
            . "- এটি তথ্যবহুল, কার্যকর এবং অনুপ্রেরণামূলক করুন\n"
            . "- বন্ধুত্বপূর্ণ এবং পেশাদার টোনে বাংলায় লিখুন\n\n"
            . "শুধুমাত্র এই ফরম্যাটে একটি JSON অবজেক্ট রিটার্ন করুন:\n"
            . '{"title": "আকর্ষণীয় পোস্টের শিরোনাম", "excerpt": "১৫০ অক্ষরের সারসংক্ষেপ", "content": "মার্কডাউন ফরম্যাটে সম্পূর্ণ পোস্টের কন্টেন্ট"}';

        try {
            $response = $aiService->chat([
                ['role' => 'user', 'content' => $prompt]
            ], [
                ...($model ? ['model' => $model] : []),
                'temperature' => 0.6,
                'max_tokens' => 4000,
                // max_tokens automatically determined by GroqService based on model
            ]);

            // Get raw markdown content (before HTML conversion)
            $responseText = is_array($response) ? ($response['raw'] ?? $response['content'] ?? '') : $response;

            \Log::info('Raw AI response received');

            // Use StructuredResponse to parse JSON (Laravel AI SDK style)
            $structured = new StructuredResponse($responseText);

            if (!$structured->isValid()) {
                \Log::warning('Failed to parse structured response', [
                    'error' => $structured->getError(),           
                ]);
                return null;
            }

            // Validate required fields
            if (!$structured->hasFields(['title', 'excerpt', 'content'])) {
                \Log::warning('Structured response missing required fields');
                return null;
            }

            \Log::info('AI generated post content successfully', [
                'title' => $structured['title'],
            ]);

            // Return as array (can use array access thanks to ArrayAccess)
            return $structured->toArray();

        } catch (\Exception $e) {
            \Log::error('Post content generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Select appropriate category based on post content
     */
    private function selectAppropriateCategory(string $title, string $contentType): ?Category
    {
        // Try to find matching category by keywords
        $keywords = [
            'workout_tips' => ['workout', 'exercise', 'training', 'strength', 'cardio'],
            'nutrition_advice' => ['nutrition', 'diet', 'food', 'meal', 'eating'],
            'motivation' => ['motivation', 'mindset', 'goal', 'inspire'],
            'wellness_tips' => ['wellness', 'health', 'recovery', 'sleep', 'mental'],
            'success_stories' => ['transformation', 'success', 'journey', 'story'],
            'qa_format' => ['question', 'answer', 'ask', 'expert'],
            'how_to_guides' => ['how to', 'guide', 'tutorial', 'step'],
            'myth_busting' => ['myth', 'fact', 'truth', 'science'],
        ];

        $typeKeywords = $keywords[$contentType] ?? [];
        
        // Find category matching keywords
        $category = Category::where('is_active', true)
            ->where(function ($q) use ($typeKeywords, $title) {
                foreach ($typeKeywords as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%");
                }
                $q->orWhere('name', 'like', "%{$title}%");
            })
            ->inRandomOrder()
            ->first();

        // Fallback to random active category
        if (!$category) {
            $category = Category::where('is_active', true)->inRandomOrder()->first();
        }

        return $category;
    }

    /**
     * Generate SEO meta fields
     */
    private function generateSEOFields($aiService, string $title, string $excerpt): array
    {
        // $prompt = "Generate SEO meta fields for a blog post titled: \"{$title}\". "
        //     . "Excerpt: {$excerpt}. "
        //     . "Return ONLY a JSON object: "
        //     . '{"meta_title": "60-char SEO title", "meta_description": "155-char description", "meta_keywords": "keyword1, keyword2, keyword3"}';

        // try {
        //     $response = $aiService->chat([
        //         ['role' => 'user', 'content' => $prompt]
        //     ], [
        //         'temperature' => 0.7,
        //         'max_tokens' => 2000,
        //     ]);

        //     $responseText = is_array($response) ? ($response['content'] ?? '') : $response;
        //     $responseText = html_entity_decode(strip_tags($responseText), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        //     if (preg_match('/\{[^}]*"meta_title"[^}]*\}/s', $responseText, $matches)) {
        //         $data = json_decode($matches[0], true);
        //         if ($data) {
        //             return $data;
        //         }
        //     }
        // } catch (\Exception $e) {
        //     \Log::warning('SEO generation failed', ['error' => $e->getMessage()]);
        // }

        // Fallback
        return [
            'meta_title' => Str::limit($title, 60),
            'meta_description' => Str::limit($excerpt, 155),
            'meta_keywords' => null,
        ];
    }

    /**
     * Generate and attach featured image
     */
    private function generateFeaturedImage(Post $post, array $postData): void
    {
        $pollinationsService = AiServiceFactory::make('pollinations');
        
        // Create image prompt based on post content
        $prompt = "Photorealistic blog thumbnail that visually expresses the core idea of the title: '{$postData['title']}'. 
Use symbolism and environment rather than portraits. 
Cinematic natural light, calm strength, balanced composition, minimal background, no text or logos.";



        \Log::info('Generating featured image for post', [
            'post_id' => $post->id,
            'prompt' => $prompt
        ]);

        // Generate image
        $imagePath = $pollinationsService->generateImage($prompt, [
            'width' => 1200,
            'height' => 630, // Standard OG image size
            'model' => 'zimage',
        ]);

        // Add to media library
        $post->addMedia($imagePath)
            ->toMediaCollection('featured_image');

        // Clean up temp file
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        \Log::info('Featured image generated and attached', [
            'post_id' => $post->id
        ]);
    }
}
