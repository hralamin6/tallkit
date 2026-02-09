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
    private function generatePostContent($aiService, array $typeConfig, string $contentType): ?array
    {
        $prompt = "Write a comprehensive, engaging fitness/health blog post about {$typeConfig['title_prompt']}. "
            . "Requirements:\n"
            . "- Length: 600-800 words (keep it concise but informative)\n"
            . "- Use bullet points and numbered lists where appropriate\n"
            . "- Use **bold** for emphasis on key points\n"
            . "- Use *italic* for subtle emphasis\n"
            . "- Include relevant emojis (💪, 🏃, 🥗, etc.) sparingly for engagement\n"
            . "- NO tables or complex formatting\n"
            . "- Include a brief call-to-action at the end\n"
            . "- Make it informative, actionable, and motivational\n"
            . "- Write in a friendly, professional tone\n\n"
            . "Return ONLY a JSON object with this exact format:\n"
            . '{"title": "Engaging Post Title", "excerpt": "150-char summary", "content": "Full post content with markdown formatting"}';

        try {
            $response = $aiService->chat([
                ['role' => 'user', 'content' => $prompt]
            ], [
                // 'temperature' => 0.2,
                // 'max_tokens' => 12000, // Increased for complete responses
            ]);

            $responseText = is_array($response) ? ($response['content'] ?? '') : $response;
            $responseText = html_entity_decode(strip_tags($responseText), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            \Log::info('Raw AI response received', [
                'length' => strlen($responseText),
                'first_200' => substr($responseText, 0, 200),
                'last_200' => substr($responseText, -200)
            ]);

            // Find JSON by locating the first { and last }
            $firstBrace = strpos($responseText, '{');
            $lastBrace = strrpos($responseText, '}');
            
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $jsonString = substr($responseText, $firstBrace, $lastBrace - $firstBrace + 1);
                
                \Log::info('Extracted JSON string', [
                    'length' => strlen($jsonString),
                    'preview' => substr($jsonString, 0, 300)
                ]);
                
                $data = json_decode($jsonString, true);
                
                if ($data && isset($data['title'], $data['excerpt'], $data['content'])) {
                    \Log::info('AI generated post content', [
                        'title' => $data['title'],
                        'excerpt_length' => strlen($data['excerpt']),
                        'content_length' => strlen($data['content'])
                    ]);
                    return $data;
                } else {
                    \Log::warning('JSON decoded but missing required fields', [
                        'has_title' => isset($data['title']),
                        'has_excerpt' => isset($data['excerpt']),
                        'has_content' => isset($data['content']),
                        'keys' => $data ? array_keys($data) : []
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
        $prompt = "Generate SEO meta fields for a blog post titled: \"{$title}\". "
            . "Excerpt: {$excerpt}. "
            . "Return ONLY a JSON object: "
            . '{"meta_title": "60-char SEO title", "meta_description": "155-char description", "meta_keywords": "keyword1, keyword2, keyword3"}';

        try {
            $response = $aiService->chat([
                ['role' => 'user', 'content' => $prompt]
            ], [
                'temperature' => 0.7,
                'max_tokens' => 200,
            ]);

            $responseText = is_array($response) ? ($response['content'] ?? '') : $response;
            $responseText = html_entity_decode(strip_tags($responseText), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (preg_match('/\{[^}]*"meta_title"[^}]*\}/s', $responseText, $matches)) {
                $data = json_decode($matches[0], true);
                if ($data) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            \Log::warning('SEO generation failed', ['error' => $e->getMessage()]);
        }

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
        $prompt = "Professional fitness/health blog featured image for article titled: \"{$postData['title']}\". "
            . "Style: modern, vibrant, motivational, high quality photography. "
            . "Content should match the topic and be visually appealing. "
            . "No text overlay, just imagery.";

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
