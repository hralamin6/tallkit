<?php

use Illuminate\Support\Facades\Route;


require __DIR__.'/auth.php';

Route::livewire('/', 'web.home')->name('web.home');


Route::middleware(['auth', 'verified'])->group(function () {
  Route::livewire('/app/', 'app.dashboard')->name('app.dashboard');

  Route::livewire('/app/profile/', 'app.profile')->name('app.profile');
  Route::livewire('/app/settings/', 'app.setting')->name('app.settings');
  Route::livewire('/app/roles/', 'app.role')->name('app.roles');
  Route::livewire('/app/users/', 'app.user')->name('app.users');
  Route::livewire('/app/backups/', 'app.backup')->name('app.backups');

  Route::livewire('/app/notifications/', 'app.notifications')->name('app.notifications');

  Route::livewire('/app/activities/feed/', 'app.activity-feed')->name('app.activity.feed');
  Route::livewire('/app/activities/my/', 'app.my-activities')->name('app.activity.my');
});


// Push notification API routes (now accessible to both guests and authenticated users)
Route::post('api/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'subscribe'])->name('push.subscribe');
Route::post('api/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe'])->name('push.unsubscribe');
Route::get('api/push/status', [\App\Http\Controllers\PushSubscriptionController::class, 'status'])->name('push.status');

// Public VAPID key endpoint (must be accessible without authentication)
Route::get('api/push/vapid-key', [\App\Http\Controllers\PushSubscriptionController::class, 'vapidPublicKey'])->name('push.vapid-key');
