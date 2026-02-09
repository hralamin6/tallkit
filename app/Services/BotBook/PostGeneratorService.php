<?php

namespace App\Services\BotBook;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\AiServiceFactory;
use Illuminate\Support\Str;

class PostGeneratorService
{
    /**
     * Post content types with their characteristics
     */
    private array $contentTypes = [
        'workout_tips' => [
            'title_prompt' => 'workout tips, exercise techniques, training methods',
            'personality_match' => ['dr_fitbot', 'coach_thunder', 'beginner_buddy'],
        ],
        'nutrition_advice' => [
            'title_prompt' => 'nutrition advice, healthy eating, meal planning, diet tips',
            'personality_match' => ['nutrition_ninja', 'dr_fitbot'],
        ],
        'motivation' => [
            'title_prompt' => 'fitness motivation, inspirational content, mindset, goal setting',
            'personality_match' => ['coach_thunder', 'beginner_buddy'],
        ],
        'wellness_tips' => [
            'title_prompt' => 'health and wellness, mental health, recovery, sleep, stress management',
            'personality_match' => ['zen_yogi', 'dr_fitbot'],
        ],
        'success_stories' => [
            'title_prompt' => 'fitness transformation stories, success journeys, before and after',
            'personality_match' => ['coach_thunder', 'beginner_buddy'],
        ],
        'qa_format' => [
            'title_prompt' => 'fitness Q&A, common questions answered, expert advice',
            'personality_match' => ['dr_fitbot', 'nutrition_ninja', 'skeptic_sam'],
        ],
        'how_to_guides' => [
            'title_prompt' => 'how-to guides, step-by-step tutorials, beginner guides',
            'personality_match' => ['beginner_buddy', 'dr_fitbot', 'zen_yogi'],
        ],
        'myth_busting' => [
            'title_prompt' => 'fitness myths debunked, fact vs fiction, science-based truth',
            'personality_match' => ['skeptic_sam', 'dr_fitbot'],
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
        $botUser = $this->selectBotUser($typeConfig['personality_match']);
        
        if (!$botUser) {
            \Log::warning('No suitable bot user found');
            return null;
        }

        // 3. Generate post content with AI (using Gemini for better long-form content)
        $aiService = AiServiceFactory::make('mistral');
        $postData = $this->generatePostContent($aiService, $typeConfig, $contentType);
        
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
    private function selectBotUser(array $preferredPersonalities): ?User
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
    private function generatePostContent($aiService, array $typeConfig, string $contentType, $model=null): ?array
    {
        $prompt = "{$typeConfig['title_prompt']} সম্পর্কে একটি বিস্তারিত এবং আকর্ষণীয় ফিটনেস/স্বাস্থ্য বিষয়ক ব্লগ পোস্ট লিখুন। "
            . "শর্তাবলী:\n"
            . "- দৈর্ঘ্য: ৬০০-৮০০ শব্দ (সংক্ষিপ্ত কিন্তু তথ্যবহুল রাখুন)\n"
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
                'temperature' => 1,
                'max_tokens' => 8000, // Increased for complete responses
            ]);

            $responseText = is_array($response) ? ($response['content'] ?? '') : $response;
            $responseText = html_entity_decode(strip_tags($responseText), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            \Log::info('Raw AI response received', [
                'length' => strlen($responseText),
                'all' => $responseText,
            ]);

            // Find JSON by locating the first { and matching closing }
            $firstBrace = strpos($responseText, '{');
            
            if ($firstBrace !== false) {
                // Find the matching closing brace by counting
                $braceCount = 0;
                $lastBrace = false;
                $inString = false;
                $escapeNext = false;
                
                for ($i = $firstBrace; $i < strlen($responseText); $i++) {
                    $char = $responseText[$i];
                    
                    if ($escapeNext) {
                        $escapeNext = false;
                        continue;
                    }
                    
                    if ($char === '\\') {
                        $escapeNext = true;
                        continue;
                    }
                    
                    if ($char === '"') {
                        $inString = !$inString;
                        continue;
                    }
                    
                    if (!$inString) {
                        if ($char === '{') {
                            $braceCount++;
                        } elseif ($char === '}') {
                            $braceCount--;
                            if ($braceCount === 0) {
                                $lastBrace = $i;
                                break;
                            }
                        }
                    }
                }
            } else {
                $lastBrace = false;
            }
            
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $jsonString = substr($responseText, $firstBrace, $lastBrace - $firstBrace + 1);
                
                \Log::info('Extracted JSON string', [
                    'length' => strlen($jsonString),
                    'preview' => substr($jsonString, 0, 300)
                ]);
                
                // Clean the JSON string - remove problematic control characters but keep \n and \t
                // Remove control chars except: \n (0x0A), \r (0x0D), \t (0x09)
                $jsonString = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $jsonString);
                $jsonString = mb_convert_encoding($jsonString, 'UTF-8', 'UTF-8'); // Fix encoding
                
                // Decode JSON with proper Unicode handling
                $data = json_decode($jsonString, true, 512, JSON_BIGINT_AS_STRING);
                $jsonError = json_last_error();
                
                if ($jsonError !== JSON_ERROR_NONE) {
                    \Log::error('JSON decode error', [
                        'error' => json_last_error_msg(),
                        'error_code' => $jsonError,
                        'json_preview' => substr($jsonString, 0, 500)
                    ]);
                }
                
                if ($data && isset($data['title'], $data['excerpt'], $data['content'])) {
                    \Log::info('AI generated post content', [
                        'title' => $data['title'],
                        'excerpt_length' => strlen($data['excerpt']),
                        'content_length' => strlen($data['content'])
                    ]);
                    return $data;
                } else {
                    \Log::warning('JSON decoded but missing required fields', [
                        'has_title' => isset($data['title']) ? 'yes' : 'no',
                        'has_excerpt' => isset($data['excerpt']) ? 'yes' : 'no',
                        'has_content' => isset($data['content']) ? 'yes' : 'no',
                        'keys' => $data ? array_keys($data) : [],
                        'data_type' => gettype($data),
                        'is_null' => $data === null ? 'yes' : 'no'
                    ]);
                }
            }

            \Log::warning('Failed to parse AI post response', [
                'response_length' => strlen($responseText),
                'first_brace_pos' => $firstBrace,
                'last_brace_pos' => $lastBrace,
                'response_preview' => substr($responseText, 0, 300),
                'json_error' => json_last_error_msg()
            ]);
        } catch (\Exception $e) {
            \Log::error('AI post content generation error', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
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
        $prompt = "Photorealistic professional blog thumbnail image inspired by the article theme: \"{$postData['title']}\". 
Scene: modern fitness environment with a modest, athletic adult male, respectful Islamic aesthetic. 
Style: high-end photography, sharp focus, natural lighting, vibrant but tasteful colors, cinematic depth of field. 
Mood: confident, disciplined, uplifting, calm strength. 
Composition: subject centered or rule-of-thirds, clean background, visually balanced, thumbnail-friendly. 
Clothing: fully modest athletic wear, long sleeves, no skin exposure beyond hands and face. 
Content rules: no women, no sexualized poses, no religious symbols used disrespectfully. 
Hard constraints: no text, no typography, no logos, no watermarks, no UI elements, no symbols, no captions.";


        \Log::info('Generating featured image for post', [
            'post_id' => $post->id,
            'prompt' => $prompt
        ]);

        // Generate image
        $imagePath = $pollinationsService->generateImage($prompt, [
            'width' => 1200,
            'height' => 630, // Standard OG image size
            'model' => 'flux',
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
