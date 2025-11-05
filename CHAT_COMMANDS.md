# Chat System - Command Reference

## 🚀 Quick Setup Commands

### Complete Setup (Automated)
```bash
chmod +x setup-chat.sh
./setup-chat.sh
```

### Manual Setup
```bash
# 1. Run migrations
php artisan migrate

# 2. Seed test data (optional)
php artisan db:seed --class=ChatSeeder

# 3. Link storage
php artisan storage:link

# 4. Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Build assets
npm run build
```

## 🔧 Development Commands

### Start Development Server
```bash
# Start all services (server, queue, logs, vite)
composer dev

# Or individually:
php artisan serve                    # Laravel server
php artisan queue:listen             # Queue worker
npm run dev                          # Vite dev server
```

### Watch for Changes
```bash
npm run dev                          # Hot reload for frontend
php artisan pail                     # Real-time logs
```

## 🗄️ Database Commands

### Migrations
```bash
# Run all pending migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Rollback all chat migrations
php artisan migrate:rollback --step=5

# Fresh migration (WARNING: deletes all data)
php artisan migrate:fresh

# Fresh with seeding
php artisan migrate:fresh --seed
```

### Seeding
```bash
# Seed chat data only
php artisan db:seed --class=ChatSeeder

# Seed all
php artisan db:seed
```

## 🧹 Cache Commands

### Clear All Caches
```bash
php artisan optimize:clear
```

### Individual Cache Clear
```bash
php artisan config:clear             # Clear config cache
php artisan route:clear              # Clear route cache
php artisan view:clear               # Clear view cache
php artisan cache:clear              # Clear application cache
```

### Cache for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔍 Debugging Commands

### Check Configuration
```bash
# View broadcasting config
php artisan tinker
>>> config('broadcasting.connections.pusher')

# Check Pusher credentials
>>> config('broadcasting.connections.pusher.key')
```

### Test Broadcasting
```bash
php artisan tinker
>>> $message = App\Models\Message::first()
>>> broadcast(new App\Events\MessageSent($message))
```

### View Routes
```bash
# List all routes
php artisan route:list

# Filter chat routes
php artisan route:list --path=chat

# Filter broadcasting routes
php artisan route:list --path=broadcasting
```

### Check Queue
```bash
# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## 📦 Asset Commands

### Build Assets
```bash
# Production build
npm run build

# Development build with watch
npm run dev
```

### Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Update dependencies
composer update
npm update
```

## 🧪 Testing Commands

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=ChatTest

# With coverage
php artisan test --coverage
```

### Create Test Data
```bash
# Create users
php artisan tinker
>>> User::factory()->count(5)->create()

# Create conversation
>>> $conv = Conversation::findOrCreateBetween(1, 2)

# Create message
>>> Message::create(['conversation_id' => $conv->id, 'user_id' => 1, 'body' => 'Test'])
```

## 🔐 Permission Commands

### Storage Permissions
```bash
# Fix storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Fix ownership (if needed)
sudo chown -R www-data:www-data storage
sudo chown -R www-data:www-data bootstrap/cache
```

### Create Storage Link
```bash
php artisan storage:link

# Verify link
ls -la public/storage
```

## 📊 Monitoring Commands

### View Logs
```bash
# Real-time logs
php artisan pail

# View log file
tail -f storage/logs/laravel.log

# Clear logs
> storage/logs/laravel.log
```

### Check Queue Status
```bash
# Monitor queue
php artisan queue:monitor

# Queue statistics
php artisan queue:work --verbose
```

## 🔄 Maintenance Commands

### Maintenance Mode
```bash
# Enable maintenance mode
php artisan down

# Enable with secret bypass
php artisan down --secret="bypass-token"

# Disable maintenance mode
php artisan up
```

### Cleanup Commands
```bash
# Clear old notifications
php artisan model:prune --model=Notification

# Clear old failed jobs
php artisan queue:prune-failed

# Clear old cache entries
php artisan cache:prune-stale-tags
```

## 🚀 Deployment Commands

### Pre-deployment
```bash
# Run tests
php artisan test

# Build assets
npm run build

# Clear and cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Post-deployment
```bash
# Run migrations
php artisan migrate --force

# Restart queue workers
php artisan queue:restart

# Clear caches
php artisan optimize:clear
```

## 🛠️ Utility Commands

### Generate Keys
```bash
# Generate app key
php artisan key:generate

# Generate VAPID keys (for push notifications)
php artisan webpush:vapid
```

### Inspect Database
```bash
php artisan tinker

# Count conversations
>>> Conversation::count()

# Count messages
>>> Message::count()

# Get recent messages
>>> Message::latest()->limit(5)->get()

# Get user conversations
>>> User::find(1)->conversations
```

### Create Components
```bash
# Create new Livewire component
php artisan make:livewire Chat/NewComponent

# Create new event
php artisan make:event NewChatEvent

# Create new notification
php artisan make:notification NewNotification
```

## 📝 Useful Tinker Commands

```bash
php artisan tinker
```

### User Operations
```php
// Get user
$user = User::find(1);

// Get user conversations
$user->conversations;

// Get unread count
$conv = Conversation::first();
$conv->getUnreadCount($user->id);

// Mark as read
$conv->markAsRead($user->id);
```

### Message Operations
```php
// Create message
$message = Message::create([
    'conversation_id' => 1,
    'user_id' => 1,
    'body' => 'Hello!'
]);

// Add reaction
MessageReaction::toggle(1, 1, '👍');

// Get message with relations
Message::with(['user', 'attachments', 'reactions'])->find(1);
```

### Conversation Operations
```php
// Find or create conversation
$conv = Conversation::findOrCreateBetween(1, 2);

// Get other user
$otherUser = $conv->getOtherUser(1);

// Get latest message
$conv->latestMessage;
```

## 🔔 Notification Commands

### Test Notifications
```bash
php artisan tinker

// Send test notification
$user = User::find(1);
$message = Message::first();
$user->notify(new \App\Notifications\NewMessageNotification($message));
```

### Check Notifications
```php
// Get user notifications
$user->notifications;

// Get unread notifications
$user->unreadNotifications;

// Mark as read
$user->unreadNotifications->markAsRead();
```

## 🎯 Quick Fixes

### Fix: Broadcasting Not Working
```bash
php artisan config:clear
php artisan route:clear
composer dump-autoload
npm run build
```

### Fix: Storage Issues
```bash
php artisan storage:link
chmod -R 775 storage
php artisan optimize:clear
```

### Fix: Queue Issues
```bash
php artisan queue:restart
php artisan queue:failed
php artisan queue:retry all
```

### Fix: Asset Issues
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

## 📚 Help Commands

```bash
# Get help for any command
php artisan help migrate
php artisan help queue:work

# List all artisan commands
php artisan list

# List specific group
php artisan list queue
php artisan list route
```

---

**Pro Tip:** Create aliases in your shell for frequently used commands:

```bash
# Add to ~/.bashrc or ~/.zshrc
alias pa="php artisan"
alias pam="php artisan migrate"
alias pac="php artisan config:clear"
alias pat="php artisan tinker"
alias nr="npm run"
alias nrd="npm run dev"
alias nrb="npm run build"
```

Then use: `pa migrate`, `nrd`, etc.
