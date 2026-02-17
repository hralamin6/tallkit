<?php

namespace App\Services\BotBook;

use App\Models\District;
use App\Models\Division;
use App\Models\Union;
use App\Models\Upazila;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\AI\AiServiceFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BotUserGeneratorService
{
    /**
     * Generate a single bot user with complete Bangladeshi data
     */
    public function generateBotUser(): User
    {
        // Generate bot profile using AI agent
        $profileData = $this->generateBotProfile();

        // Get random location data
        $locationData = $this->getRandomBangladeshiLocation();

        // Create user first to get ID for email
        $user = User::create([
            'name' => $profileData['name'],
            'email' => 'temp_'.Str::random(10).'@botbook.local', // Temporary email
            'password' => Hash::make('password'), // Random secure password
            'email_verified_at' => now(),
        ]);

        // Update email with user ID
        $user->update([
            'email' => $user->id.'@botbook.local',
        ]);

        // Create user details
        UserDetail::create([
            'user_id' => $user->id,
            'phone' => $this->generateBangladeshiPhone(),
            'date_of_birth' => $this->generateRandomDateOfBirth(),
            'gender' => $profileData['gender'],
            'address' => $profileData['address'],
            'postal_code' => $locationData['postal_code'],
            'occupation' => 'student',
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
            $this->generateProfileImage($user, $profileData);
        } catch (\Exception $e) {
            \Log::warning('Failed to generate profile image for bot', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Generate and attach banner image
        try {
            $this->generateBannerImage($user, $profileData);
        } catch (\Exception $e) {
            \Log::warning('Failed to generate banner image for bot', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $user;
    }

    /**
     * Generate profile image using Pollinations AI
     */
    private function generateProfileImage(User $user, array $profileData): void
    {
        $pollinationsService = AiServiceFactory::make('pollinations');

        // Create image prompt based on bot profile and gender
        $gender = $profileData['gender'] === 'male' ? 'Bangladeshi man' : 'Bangladeshi woman';
        $ageRange = '30-45 years old';

        $prompt = "Professional headshot portrait photo of a {$gender}, {$ageRange}, "
            .'confident smile, professional attire, '
            .'studio lighting, high quality, realistic, '
            .'South Asian features, professional photography';

        \Log::info('Generating profile image', [
            'user_id' => $user->id,
            'prompt' => $prompt,
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
            'name' => $user->name,
        ]);
    }

    /**
     * Generate banner image using Pollinations AI
     */
    private function generateBannerImage(User $user, array $profileData): void
    {
        $pollinationsService = AiServiceFactory::make('pollinations');

        // Create banner prompt based on random themes
        $themes = [
            'modern gym with equipment',
            'yoga studio with natural light',
            'outdoor fitness park',
            'nutrition and healthy food',
            'meditation and wellness space',
            'running track at sunrise',
            'fitness training facility',
            'health and wellness center',
        ];

        $theme = $themes[array_rand($themes)];

        $prompt = "Professional fitness banner image, {$theme}, "
            .'vibrant colors, motivational atmosphere, '
            .'high quality, modern, clean design, '
            .'wide angle, professional photography, '
            .'inspiring fitness environment';

        \Log::info('Generating banner image', [
            'user_id' => $user->id,
            'theme' => $theme,
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
            'name' => $user->name,
        ]);
    }

    /**
     * Generate bot profile data using AI
     */
    private function generateBotProfile(): array
    {
        $prompt = 'একজন বাংলাদেশী ব্যবহারকারীর সম্পূর্ণ প্রোফাইল তৈরি করো। '
            .'নাম, লিঙ্গ, ঠিকানা এবং একটি বিস্তারিত পেশাদার বায়ো লিখো।';

        try {
            $response = \App\Ai\Agents\BotUserGenerator::make()
                ->prompt($prompt);

            if (! $response) {
                \Log::warning('BotUserGenerator returned empty response');

                return $this->getFallbackProfile();
            }

            // The SDK handles structured output validation based on the schema
            $structured = $response->structured;

            \Log::info('AI generated bot profile successfully using BotUserGenerator', [
                'name' => $structured['name'] ?? 'N/A',
            ]);

            return (array) $structured;

        } catch (\Exception $e) {
            \Log::error('BotUserGenerator generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->getFallbackProfile();
        }
    }

    /**
     * Get fallback profile data if AI fails
     */
    private function getFallbackProfile(): array
    {
        $names = [
            'male' => ['আব্দুল করিম', 'মোহাম্মদ রহিম', 'আলী আহমেদ', 'সাজেদুল ইসলাম'],
            'female' => ['ফাতেমা বেগম', 'রুবিনা আক্তার', 'নাজমা খাতুন', 'সাবিনা ইয়াসমিন'],
        ];

        $gender = rand(0, 1) ? 'male' : 'female';
        $name = $names[$gender][array_rand($names[$gender])];

        return [
            'name' => $name,
            'gender' => $gender,
            'address' => 'মির্জাপুর গ্রাম',
            'bio' => 'একজন উৎসাহী এবং প্রতিশ্রুতিবদ্ধ পেশাদার যিনি সমাজের উন্নয়নে কাজ করতে আগ্রহী।',
        ];
    }

    /**
     * Get random Bangladeshi location data
     */
    private function getRandomBangladeshiLocation(): array
    {
        // Get random division
        $division = Division::inRandomOrder()->first();

        if (! $division) {
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
     * Fallba
     * Get all available bot types
     */

    /**
     * Generate multiple bot users
     */
    public function generateMultipleBots(int $count = 5): array
    {
        $generatedBots = [];

        for ($i = 0; $i < $count; $i++) {
            // Pick a random bot type

            try {
                $bot = $this->generateBotUser();
                $bot->assignRole('user');
                $generatedBots[] = $bot;

                \Log::info("Bot user created: {$bot->name} )");
            } catch (\Exception $e) {
                \Log::error('Failed to create bot user: '.$e->getMessage());
            }

            // Small delay to avoid rate limiting
            sleep(2);
        }

        return $generatedBots;
    }
}
