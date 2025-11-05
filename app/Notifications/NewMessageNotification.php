<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // Add push notification if user has enabled it
        if ($notifiable->isPushEnabledFor('chat')) {
//            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New message from ' . $this->message->user->name)
            ->line($this->message->user->name . ' sent you a message.')
            ->line('"' . \Illuminate\Support\Str::limit($this->message->body, 100) . '"')
            ->action('View Message', route('chat.index', ['conversation' => $this->message->conversation_id]))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('New message from ' . $this->message->user->name)
            ->icon('/logo.png')
            ->body(\Illuminate\Support\Str::limit($this->message->body ?? 'Sent an attachment', 100))
            ->action('View', 'view_message')
            ->data([
                'url' => route('chat.index', ['conversation' => $this->message->conversation_id]),
                'conversation_id' => $this->message->conversation_id,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->user_id,
            'sender_name' => $this->message->user->name,
            'sender_avatar' => $this->message->user->avatar_url,
            'body' => \Illuminate\Support\Str::limit($this->message->body ?? 'Sent an attachment', 100),
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}
