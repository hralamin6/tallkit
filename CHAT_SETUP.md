# Chat System - Quick Setup Guide

## 🚀 Quick Start

### 1. Configure Pusher

Add these credentials to your `.env` file:

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

**Don't have Pusher credentials?**
1. Sign up at [pusher.com](https://pusher.com)
2. Create a new app
3. Copy your credentials from the "App Keys" section

### 2. Run Setup Script

```bash
chmod +x setup-chat.sh
./setup-chat.sh
```

Or manually:

```bash
# Run migrations
php artisan migrate

# (Optional) Seed test data
php artisan db:seed --class=ChatSeeder

# Link storage
php artisan storage:link

# Clear caches
php artisan config:clear
php artisan route:clear
```

### 3. Build Assets

```bash
npm run build
# or for development
npm run dev
```

### 4. Start Using Chat

Navigate to `/app/chat/` in your browser or click "Chat" in the sidebar menu.

## ✨ Features Included

- ✅ Real-time messaging with Pusher
- ✅ File attachments (images, documents, videos)
- ✅ Message reactions (emojis)
- ✅ Message threading (replies)
- ✅ Message editing & deletion
- ✅ Read receipts
- ✅ Typing indicators
- ✅ Message search
- ✅ Push notifications
- ✅ Responsive design

## 📁 What Was Created

### Database
- `conversations` - Stores conversations
- `messages` - Stores messages
- `message_attachments` - File attachments
- `message_reactions` - Emoji reactions
- `conversation_user` - Pivot table with read status

### Models
- `App\Models\Conversation`
- `App\Models\Message`
- `App\Models\MessageAttachment`
- `App\Models\MessageReaction`

### Livewire Components
- `App\Livewire\Chat\Index` - Main chat interface
- `App\Livewire\Chat\MessageList` - Message display
- `App\Livewire\Chat\MessageInput` - Message composition

### Events (Pusher)
- `MessageSent` - New message broadcast
- `MessageUpdated` - Message edited
- `MessageDeleted` - Message removed
- `MessageReacted` - Reaction added/removed
- `UserTyping` - Typing indicator

### Views
- `resources/views/livewire/chat/index.blade.php`
- `resources/views/livewire/chat/message-list.blade.php`
- `resources/views/livewire/chat/message-input.blade.php`

### Routes
- `/app/chat/` - Main chat interface
- `/broadcasting/auth` - Pusher authentication

## 🔧 Configuration

### File Upload Settings

Edit `app/Livewire/Chat/MessageInput.php`:

```php
protected $rules = [
    'attachments.*' => 'file|max:10240', // 10MB max
];
```

### Emoji Reactions

Edit `resources/views/livewire/chat/message-list.blade.php`:

```php
@foreach(['👍', '❤️', '😂', '😮', '😢', '🙏', '🎉', '🔥', '👏', '✨', '💯', '🚀'] as $emoji)
```

### Message Pagination

Edit `app/Livewire/Chat/MessageList.php`:

```php
return $query->orderBy('created_at', 'asc')->paginate(50);
```

## 🐛 Troubleshooting

### Messages not appearing in real-time

**Check Pusher credentials:**
```bash
php artisan config:clear
php artisan tinker
>>> config('broadcasting.connections.pusher')
```

**Check browser console:**
- Open DevTools (F12)
- Look for WebSocket connection errors
- Verify Echo is initialized

**Test Pusher connection:**
```bash
php artisan tinker
>>> broadcast(new App\Events\MessageSent(App\Models\Message::first()));
```

### File uploads failing

**Check storage link:**
```bash
php artisan storage:link
ls -la public/storage
```

**Check permissions:**
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

**Check PHP upload limits:**
```bash
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

### Notifications not working

**Check notification preferences:**
```php
$user->getNotificationPreference('chat');
```

**Check WebPush setup:**
- Verify VAPID keys are set
- Check if user has subscribed to push notifications

## 📚 Full Documentation

For detailed documentation, see:
- **[docs/CHAT_SYSTEM_GUIDE.md](docs/CHAT_SYSTEM_GUIDE.md)** - Complete guide with API reference

## 🎯 Usage Examples

### Start a Conversation

```php
use App\Models\Conversation;

$conversation = Conversation::findOrCreateBetween($userId1, $userId2);
```

### Send a Message

```php
use App\Models\Message;

$message = Message::create([
    'conversation_id' => $conversationId,
    'user_id' => auth()->id(),
    'body' => 'Hello!',
]);
```

### Add a Reaction

```php
use App\Models\MessageReaction;

MessageReaction::toggle($messageId, auth()->id(), '👍');
```

### Get Unread Count

```php
$unreadCount = $conversation->getUnreadCount(auth()->id());
```

### Mark as Read

```php
$conversation->markAsRead(auth()->id());
```

## 🔐 Security

- ✅ Private channels (only conversation participants can listen)
- ✅ Message ownership validation
- ✅ File upload validation and size limits
- ✅ XSS protection (Laravel's built-in escaping)
- ✅ CSRF protection on all forms

## 🚦 Testing

### Manual Testing

1. Create two user accounts
2. Login as User A
3. Start a conversation with User B
4. Send messages, attachments, reactions
5. Login as User B in another browser/incognito
6. Verify real-time updates

### With Seeded Data

```bash
php artisan db:seed --class=ChatSeeder
```

This creates a conversation between the first two users with sample messages.

## 📊 Performance

- Messages paginated (50 per page)
- Eager loading to prevent N+1 queries
- Database indexes on frequently queried columns
- Efficient WebSocket connections via Pusher

## 🎨 Customization

The chat UI uses **DaisyUI** components with **Tailwind CSS**. Customize by:

1. Editing Blade templates in `resources/views/livewire/chat/`
2. Modifying Tailwind classes
3. Updating DaisyUI theme in `tailwind.config.js`

## 🆘 Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review `docs/CHAT_SYSTEM_GUIDE.md`
3. Check Laravel logs: `storage/logs/laravel.log`
4. Check browser console for JavaScript errors
5. Verify Pusher dashboard for connection issues

## 📝 License

This chat system is part of your Laravel application and follows the same license.
