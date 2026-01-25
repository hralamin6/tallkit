<?php

use App\Models\GuestSubscription;
use Livewire\Attributes\Layout;
use Livewire\Component;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

new
#[Layout('layouts.auth')]
class extends Component
{
    private function sendGuestNotifications()
    {
        $guestSubscriptions = GuestSubscription::all();

        if ($guestSubscriptions->isEmpty()) {
            return;
        }

        // Initialize WebPush with VAPID keys
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ]);

        foreach ($guestSubscriptions as $guestSub) {
            try {
                // Create subscription object
                $subscription = Subscription::create([
                    'endpoint' => $guestSub->endpoint,
                    'publicKey' => $guestSub->public_key,
                    'authToken' => $guestSub->auth_token,
                    'contentEncoding' => $guestSub->content_encoding,
                ]);

                // Create notification payload
                $payload = json_encode([
                    'title' => 'Welcome Guest!',
                    'body' => 'This is a test notification for guest users',
                    'icon' => '/logo.png',
                    'badge' => '/logo.png',
                    'data' => [
                        'url' => '/',
                        'timestamp' => now()->toISOString(),
                    ],
                ]);

                // Send notification
                $result = $webPush->sendOneNotification($subscription, $payload);

                if (!$result->isSuccess()) {
                    \Log::error('Failed to send push notification to guest: ' . $result->getReason());
                }

            } catch (\Exception $e) {
                \Log::error('Error sending push notification to guest: ' . $e->getMessage());
            }
        }

        // Flush any remaining notifications
        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                \Log::error('Push notification failed: ' . $report->getReason());
            }
        }
    }
};
