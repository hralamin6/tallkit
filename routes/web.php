<?php

use App\Ai\Agents\PostWriter;
use App\Services\AI\AiServiceFactory;
use App\Services\BotBook\StructuredResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Ai;
use Laravel\Ai\Image;

require __DIR__.'/auth.php';
Route::get('/image', function () {
    $response = Image::of('A donut sitting on the kitchen counter')->generate();
    $image = $response->images[0];

    $path = $image->store();
    $path = $image->storeAs('image.jpg');
    $path = $image->storePublicly();
    $path = $image->storePubliclyAs('image.jpg');

    return $path;

});

Route::get('/ai', function () {
    $response = PostWriter::make()
        ->prompt('about islam');
    return  $response;

});
Route::get('/test', function () {
    $prompt = 'একটি বিস্তারিত এবং আকর্ষণীয় ফিটনেস/স্বাস্থ্য বিষয়ক ব্লগ পোস্ট লিখুন। '
                ."শর্তাবলী:\n"
                ."- দৈর্ঘ্য: ১০০-৯০০ শব্দ (সংক্ষিপ্ত কিন্তু তথ্যবহুল রাখুন)\n"
                ."- যথাযথ স্থানে বুলেট পয়েন্ট এবং সংখ্যায়িত তালিকা ব্যবহার করুন\n"
                ."- মূল পয়েন্টগুলোতে জোর দেওয়ার জন্য **বোল্ড** ব্যবহার করুন\n"
                ."- সূক্ষ্ম গুরুত্ব বোঝাতে *ইটালিক* ব্যবহার করুন\n"
                ."- ব্যস্ততা বাড়াতে প্রাসঙ্গিক ইমোজি (💪, 🏃, 🥗, ইত্যাদি) পরিমিতভাবে ব্যবহার করুন\n"
                ."- কোনো টেবিল বা জটিল ফরম্যাটিং ব্যবহার করবেন না\n"
                ."- শেষে একটি সংক্ষিপ্ত কল-টু-অ্যাকশন অন্তর্ভুক্ত করুন\n"
                ."- এটি তথ্যবহুল, কার্যকর এবং অনুপ্রেরণামূলক করুন\n"
                ."- বন্ধুত্বপূর্ণ এবং পেশাদার টোনে বাংলায় লিখুন\n\n"
                ."শুধুমাত্র এই ফরম্যাটে একটি JSON অবজেক্ট রিটার্ন করুন:\n"
                .'{"title": "আকর্ষণীয় পোস্টের শিরোনাম", "excerpt": "১৫০ অক্ষরের সারসংক্ষেপ", "content": "মার্কডাউন ফরম্যাটে সম্পূর্ণ পোস্টের কন্টেন্ট", "image_prompt": "write a nice small blog post image prompt in english for this post"}';
    // Get new response
    $response = AiServiceFactory::make('custom')->chat([['role' => 'user', 'content' => $prompt]], ['model' => 'post', 'max_tokens' => 19000]);
    $structured = new StructuredResponse($response['content']);
    if ($structured->isValid() && $structured->hasFields(['title', 'excerpt', 'content'])) {
        return $structured->toArray();
    } else {
        return $structured->getError();
    }
});


Route::livewire('/', 'web::home')->name('web.home');
Route::livewire('/posts', 'web::posts')->name('web.posts');
Route::livewire('/posts/{slug}', 'web::post')->name('web.post');
Route::livewire('/categories', 'web::categories')->name('web.categories');
Route::livewire('/users', 'web::users')->name('web.users');
Route::livewire('/users/{id}', 'web::user')->name('web.user');

Route::middleware('auth')->group(function () {
    Route::livewire('/app/', 'app::dashboard')->name('app.dashboard');

    Route::livewire('/app/profile/', 'app::profile')->name('app.profile');
    Route::livewire('/app/settings/', 'app::settings')->name('app.settings');
    Route::livewire('/app/roles/', 'app::roles')->name('app.roles');
    Route::livewire('/app/users/', 'app::users')->name('app.users');
    Route::livewire('/app/backups/', 'app::backups')->name('app.backups');
    Route::livewire('/app/translate/', 'app::translate')->name('app.translate');
    Route::livewire('/app/pages/', 'app::pages')->name('app.pages');
    Route::livewire('/app/categories/', 'app::categories')->name('app.categories');
    Route::livewire('/app/posts/', 'app::posts')->name('app.posts');

    Route::livewire('/app/notifications/', 'app::notifications')->name('app.notifications');

    Route::livewire('/app/activities/feed/', 'app::activity-feed')->name('app.activity.feed');
    Route::livewire('/app/activities/my/', 'app::my-activities')->name('app.activity.my');

    // Chat routes
    Route::livewire('/app/chat/{conversation?}', 'app::chat')->name('app.chat');
    Route::livewire('/app/ai-chat/{conversation?}', 'app::ai-chat')->name('app.ai-chat');
});

// Push notification API routes (now accessible to both guests and authenticated users)
Route::post('api/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'subscribe'])->name('push.subscribe');
Route::post('api/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe'])->name('push.unsubscribe');
Route::get('api/push/status', [\App\Http\Controllers\PushSubscriptionController::class, 'status'])->name('push.status');

// Public VAPID key endpoint (must be accessible without authentication)
Route::get('api/push/vapid-key', [\App\Http\Controllers\PushSubscriptionController::class, 'vapidPublicKey'])->name('push.vapid-key');

Route::livewire('{slug}', 'web::page')->name('web.page');
