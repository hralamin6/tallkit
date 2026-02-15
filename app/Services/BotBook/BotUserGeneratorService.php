<?php

namespace App\Services\BotBook;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\Division;
use App\Models\District;
use App\Models\Upazila;
use App\Models\Union;
use App\Services\AI\AiServiceFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BotUserGeneratorService
{
    private array $botPersonalities = [
        'dr_fitbot' => [
            'name_prefix' => 'ডা.',
            'expertise' => 'চিকিৎসা ও ব্যায়াম বিজ্ঞান',
            'ai_provider' => 'gemini',
        ],
        'coach_thunder' => [
            'name_prefix' => 'কোচ',
            'expertise' => 'শক্তি প্রশিক্ষণ ও মোটিভেশন',
            'ai_provider' => 'groq',
        ],
        'zen_yogi' => [
            'name_prefix' => 'যোগী',
            'expertise' => 'যোগব্যায়াম ও ধ্যান',
            'ai_provider' => 'mistral',
        ],
        'nutrition_ninja' => [
            'name_prefix' => 'পুষ্টিবিদ',
            'expertise' => 'পুষ্টি বিজ্ঞান ও খাদ্য পরিকল্পনা',
            'ai_provider' => 'cerebras',
        ],
        'cardio_queen' => [
            'name_prefix' => 'কার্ডিও',
            'expertise' => 'সহনশীলতা প্রশিক্ষণ ও দৌড়',
            'ai_provider' => 'openrouter',
        ],
        'skeptic_sam' => [
            'name_prefix' => 'বিশ্লেষক',
            'expertise' => 'ফিটনেস মিথ বাস্টার',
            'ai_provider' => 'pollinations',
        ],
        'beginner_buddy' => [
            'name_prefix' => 'সহায়ক',
            'expertise' => 'শুরুর জন্য ফিটনেস',
            'ai_provider' => 'gemini',
        ],
        'biohacker_beta' => [
            'name_prefix' => 'বায়োহ্যাকার',
            'expertise' => 'প্রযুক্তি ও অপটিমাইজেশন',
            'ai_provider' => 'groq',
        ],
    ];

    /**
     * Generate a single bot user with complete Bangladeshi data
     */
    public function generateBotUser(string $botType): User
    {
        $personality = $this->botPersonalities[$botType];
        $aiService = AiServiceFactory::make('nvidia');

        // Generate bot profile using AI
        $profileData = $this->generateBotProfile($aiService, $personality, $botType);

        // Get random location data
        $locationData = $this->getRandomBangladeshiLocation();

        // Create user first to get ID for email
        $user = User::create([
            'name' => $profileData['name'],
            'email' => 'temp_' . Str::random(10) . '@botbook.local', // Temporary email
            'password' => Hash::make('password'), // Random secure password
            'email_verified_at' => now(),
        ]);

        // Update email with user ID
        $user->update([
            'email' => $user->id . '@botbook.local'
        ]);

        // Create user details
        UserDetail::create([
            'user_id' => $user->id,
            'phone' => $this->generateBangladeshiPhone(),
            'date_of_birth' => $this->generateRandomDateOfBirth(),
            'gender' => $profileData['gender'],
            'address' => $profileData['address'],
            'postal_code' => $locationData['postal_code'],
            'occupation' => $personality['expertise'],
            'bio' => $profileData['bio'],
            'division_id' => $locationData['division_id'],
            'district_id' => $locationData['district_id'],
            'upazila_id' => $locationData['upazila_id'],
            'union_id' => $locationData['union_id'],
            'is_active' => true,
        ]);

        // Assign user role
        $user->assignRole('user');

        // Generate and attach profile image
        try {
            $this->generateProfileImage($user, $profileData, $personality);
        } catch (\Exception $e) {
            \Log::warning('Failed to generate profile image for bot', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        // Generate and attach banner image
        try {
            $this->generateBannerImage($user, $profileData, $personality);
        } catch (\Exception $e) {
            \Log::warning('Failed to generate banner image for bot', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        return $user;
    }

    /**
     * Generate profile image using Pollinations AI
     */
    private function generateProfileImage(User $user, array $profileData, array $personality): void
    {
        $pollinationsService = AiServiceFactory::make('pollinations');
        
        // Create image prompt based on bot personality and gender
        $gender = $profileData['gender'] === 'male' ? 'Bangladeshi man' : 'Bangladeshi woman';
        $ageRange = '30-45 years old';
        
        $prompt = "Professional headshot portrait photo of a {$gender}, {$ageRange}, "
            . "fitness expert, {$personality['expertise']}, "
            . "confident smile, professional attire, "
            . "studio lighting, high quality, realistic, "
            . "South Asian features, professional photography";
        
        \Log::info('Generating profile image', [
            'user_id' => $user->id,
            'prompt' => $prompt
        ]);

        // Generate image (returns temp file path)
        $imagePath = $pollinationsService->generateImage($prompt, [
            'width' => 512,
            'height' => 512,
            'model' => 'flux',
        ]);

        // Add to media library (profile collection)
        $user->addMedia($imagePath)
            ->toMediaCollection('profile');

        // Clean up temp file
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        \Log::info('Profile image generated and attached', [
            'user_id' => $user->id,
            'name' => $user->name
        ]);
    }

    /**
     * Generate banner image using Pollinations AI
     */
    private function generateBannerImage(User $user, array $profileData, array $personality): void
    {
        $pollinationsService = AiServiceFactory::make('pollinations');
        
        // Create banner prompt based on bot personality
        $themes = [
            'modern gym with equipment',
            'yoga studio with natural light',
            'outdoor fitness park',
            'nutrition and healthy food',
            'meditation and wellness space',
            'running track at sunrise',
            'fitness training facility',
            'health and wellness center'
        ];
        
        $theme = $themes[array_rand($themes)];
        
        $prompt = "Professional fitness banner image, {$theme}, "
            . "vibrant colors, motivational atmosphere, "
            . "high quality, modern, clean design, "
            . "wide angle, professional photography, "
            . "inspiring fitness environment";
        
        \Log::info('Generating banner image', [
            'user_id' => $user->id,
            'theme' => $theme
        ]);

        // Generate banner image (wider aspect ratio)
        $imagePath = $pollinationsService->generateImage($prompt, [
            'width' => 1200,
            'height' => 400,
            'model' => 'flux',
        ]);

        // Add to media library (banner collection)
        $user->addMedia($imagePath)
            ->toMediaCollection('banner');

        // Clean up temp file
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        \Log::info('Banner image generated and attached', [
            'user_id' => $user->id,
            'name' => $user->name
        ]);
    }

    /**
     * Generate bot profile data using AI
     */
    private function generateBotProfile($aiService, array $personality, string $botType): array
    {
        $prompt = "তুমি একজন বাংলাদেশী  এর প্রোফাইল তৈরি করছো।

বট টাইপ: {$botType}
দক্ষতা: {$personality['expertise']}
নাম প্রিফিক্স: {$personality['name_prefix']}

নিচের ফরম্যাটে JSON আকারে তথ্য দাও (শুধুমাত্র JSON, অন্য কিছু না):
{
    \"name\": \"সম্পূর্ণ বাংলা নাম\",
    \"gender\": \"male অথবা female\",
    \"address\": \"সম্পূর্ণ বাংলা ঠিকানা (গ্রাম/মহল্লা নাম)\",
    \"bio\": \"150-200 শব্দের বাংলায় পেশাদার বায়ো যেখানে দক্ষতা, অভিজ্ঞতা এবং ফিটনেস দর্শন উল্লেখ থাকবে\"
}

নিশ্চিত করো:
- নাম সম্পূর্ণ বাংলায় এবং বাস্তবসম্মত বাংলাদেশী নাম
- ঠিকানা শুধু গ্রাম/মহল্লার নাম (বিভাগ/জেলা ছাড়া)
- বায়ো পেশাদার, তথ্যবহুল এবং অনুপ্রেরণামূলক
- লিঙ্গ অনুযায়ী উপযুক্ত নাম";

        try {
            $response = $aiService->chat([
                ['role' => 'user', 'content' => $prompt]
            ], [
                'temperature' => 0.8,
                'max_tokens' => 800, // Increased to ensure complete bio
            ]);

            // Extract content from response array
            $responseText = is_array($response) ? ($response['content'] ?? '') : $response;
            
            // Strip HTML tags and decode HTML entities (Gemini returns HTML)
            $responseText = html_entity_decode(strip_tags($responseText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            // Extract JSON from response text
            $jsonMatch = [];
            if (preg_match('/\{[\s\S]*\}/', $responseText, $jsonMatch)) {
                $jsonString = trim($jsonMatch[0]);
                $data = json_decode($jsonString, true);
                
                if ($data && isset($data['name'], $data['gender'], $data['address'], $data['bio'])) {
                    \Log::info('AI generated bot profile successfully', [
                        'bot_type' => $botType, 
                        'name' => $data['name'],
                        'gender' => $data['gender']
                    ]);
                    return $data;
                }
            }
            
            \Log::warning('Failed to parse AI response, using fallback', [
                'response' => substr($responseText, 0, 300),
                'bot_type' => $botType
            ]);
        } catch (\Exception $e) {
            \Log::error('Bot profile generation failed: ' . $e->getMessage(), [
                'bot_type' => $botType,
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Fallback to default data if AI fails
        return $this->getFallbackProfile($personality, $botType);
    }

    /**
     * Get random Bangladeshi location data
     */
    private function getRandomBangladeshiLocation(): array
    {
        // Get random division
        $division = Division::inRandomOrder()->first();
        
        if (!$division) {
            throw new \Exception('No divisions found in database. Please seed location data first.');
        }

        // Get random district from this division
        $district = District::where('division_id', $division->id)
            ->inRandomOrder()
            ->first();

        // Get random upazila from this district
        $upazila = Upazila::where('district_id', $district->id)
            ->inRandomOrder()
            ->first();

        // Get random union from this upazila
        $union = Union::where('upazila_id', $upazila->id)
            ->inRandomOrder()
            ->first();

        return [
            'division_id' => $division->id,
            'district_id' => $district->id,
            'upazila_id' => $upazila->id,
            'union_id' => $union->id,
            'postal_code' => rand(1000, 9999),
        ];
    }

    /**
     * Generate bot email
     */
    private function generateBotEmail(string $botType): string
    {
        $timestamp = now()->timestamp;
        $random = Str::random(6);
        return "{$botType}_{$timestamp}_{$random}@botbook.local";
    }

    /**
     * Generate Bangladeshi phone number
     */
    private function generateBangladeshiPhone(): string
    {
        $operators = ['013', '014', '015', '016', '017', '018', '019'];
        $operator = $operators[array_rand($operators)];
        $number = rand(10000000, 99999999);
        return "{$operator}{$number}";
    }

    /**
     * Generate random date of birth (25-55 years old)
     */
    private function generateRandomDateOfBirth(): string
    {
        $yearsAgo = rand(25, 55);
        return now()->subYears($yearsAgo)->subDays(rand(0, 365))->format('Y-m-d');
    }

    /**
     * Fallback profile data if AI generation fails
     */
    private function getFallbackProfile(array $personality, string $botType): array
    {
        $maleNames = [
            'ডা. রহিম উদ্দিন',
            'কোচ করিম আহমেদ',
            'যোগী সালাম মিয়া',
            'পুষ্টিবিদ জাহিদ হাসান',
            'কার্ডিও রফিক উল্লাহ',
            'বিশ্লেষক নাসির উদ্দিন',
            'সহায়ক মাহমুদ হোসেন',
            'বায়োহ্যাকার ফারুক আহমেদ',
        ];

        $femaleNames = [
            'ডা. সাবিনা খাতুন',
            'কোচ নাজমা আক্তার',
            'যোগী রুমানা বেগম',
            'পুষ্টিবিদ ফারহানা ইসলাম',
            'কার্ডিও শাহিনা পারভীন',
            'বিশ্লেষক তাসনিম জাহান',
            'সহায়ক সুমাইয়া রহমান',
            'বায়োহ্যাকার নুসরাত জাহান',
        ];

        $addresses = [
            'শান্তিনগর',
            'কামালপুর',
            'রহিমনগর',
            'আজিমপুর',
            'নূরপুর',
            'সাদিকপুর',
            'মতিনগর',
            'হাসানপুর',
        ];

        $gender = rand(0, 1) ? 'male' : 'female';
        $names = $gender === 'male' ? $maleNames : $femaleNames;

        return [
            'name' => $names[array_rand($names)],
            'gender' => $gender,
            'address' => $addresses[array_rand($addresses)],
            'bio' => "আমি একজন অভিজ্ঞ {$personality['expertise']} বিশেষজ্ঞ। ১০+ বছরের অভিজ্ঞতা নিয়ে মানুষকে সুস্থ ও ফিট থাকতে সাহায্য করছি। বিজ্ঞানভিত্তিক পদ্ধতি এবং ব্যক্তিগত যত্নে বিশ্বাসী। আমার লক্ষ্য হলো প্রত্যেককে তাদের ফিটনেস লক্ষ্য অর্জনে সহায়তা করা এবং একটি স্বাস্থ্যকর জীবনযাপনে অনুপ্রাণিত করা।",
        ];
    }

    /**
     * Get all available bot types
     */
    public function getAvailableBotTypes(): array
    {
        return array_keys($this->botPersonalities);
    }

    /**
     * Generate multiple bot users
     */
    public function generateMultipleBots(int $count = 5): array
    {
        $botTypes = $this->getAvailableBotTypes();
        $generatedBots = [];

        for ($i = 0; $i < $count; $i++) {
            // Pick a random bot type
            $botType = $botTypes[array_rand($botTypes)];
            
            try {
                $bot = $this->generateBotUser($botType);
                $bot->assignRole('user');
                $generatedBots[] = $bot;
                
                \Log::info("Bot user created: {$bot->name} ({$botType})");
            } catch (\Exception $e) {
                \Log::error("Failed to create bot user: " . $e->getMessage());
            }

            // Small delay to avoid rate limiting
            sleep(2);
        }

        return $generatedBots;
    }
}
