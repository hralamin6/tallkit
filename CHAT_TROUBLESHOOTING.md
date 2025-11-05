# Chat System Troubleshooting

## WebSocket Connection Error

If you see errors like:
```
Firefox can't establish a connection to the server at wss://ws-ap2.pusher.com/...
```

This means the Pusher WebSocket connection is failing. Here are solutions:

### Option 1: Verify Pusher Credentials (Recommended)

1. **Check your Pusher account:**
   - Login to https://dashboard.pusher.com
   - Verify your app is active
   - Check if you've exceeded free tier limits
   - Ensure the cluster matches (ap2, us2, eu, etc.)

2. **Verify .env configuration:**
   ```env
   BROADCAST_CONNECTION=pusher
   PUSHER_APP_ID=your_app_id
   PUSHER_APP_KEY=your_app_key
   PUSHER_APP_SECRET=your_app_secret
   PUSHER_APP_CLUSTER=ap2  # Must match your Pusher app cluster
   
   VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
   VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
   ```

3. **Clear caches and rebuild:**
   ```bash
   php artisan config:clear
   npm run build
   # Then refresh your browser
   ```

4. **Test Pusher connection:**
   ```bash
   php artisan tinker
   >>> config('broadcasting.connections.pusher.key')
   >>> config('broadcasting.connections.pusher.cluster')
   ```

### Option 2: Use Without Real-time (Temporary Solution)

If you want to use the chat without real-time updates temporarily:

1. **Disable broadcasting in .env:**
   ```env
   BROADCAST_CONNECTION=log
   ```

2. **Clear config:**
   ```bash
   php artisan config:clear
   ```

3. **The chat will still work, but:**
   - ✅ Messages will be sent
   - ✅ Messages will appear after page refresh
   - ❌ No real-time updates
   - ❌ No typing indicators
   - ❌ Manual refresh needed to see new messages

### Option 3: Use Laravel Reverb (Alternative to Pusher)

Laravel Reverb is a free, first-party WebSocket server:

1. **Install Reverb:**
   ```bash
   composer require laravel/reverb
   php artisan reverb:install
   ```

2. **Update .env:**
   ```env
   BROADCAST_CONNECTION=reverb
   
   REVERB_APP_ID=your-app-id
   REVERB_APP_KEY=your-app-key
   REVERB_APP_SECRET=your-app-secret
   REVERB_HOST="localhost"
   REVERB_PORT=8080
   REVERB_SCHEME=http
   
   VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
   VITE_REVERB_HOST="${REVERB_HOST}"
   VITE_REVERB_PORT="${REVERB_PORT}"
   VITE_REVERB_SCHEME="${REVERB_SCHEME}"
   ```

3. **Update bootstrap.js:**
   ```javascript
   import Echo from 'laravel-echo';
   import Pusher from 'pusher-js';
   
   window.Pusher = Pusher;
   
   window.Echo = new Echo({
       broadcaster: 'reverb',
       key: import.meta.env.VITE_REVERB_APP_KEY,
       wsHost: import.meta.env.VITE_REVERB_HOST,
       wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
       wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
       forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
       enabledTransports: ['ws', 'wss'],
   });
   ```

4. **Start Reverb server:**
   ```bash
   php artisan reverb:start
   ```

5. **Rebuild assets:**
   ```bash
   npm run build
   ```

### Option 4: Check Network/Firewall Issues

1. **Test Pusher connectivity:**
   - Open https://pusher.com/docs/channels/library_auth_reference/pusher-websockets-protocol
   - Try connecting from your browser's console
   
2. **Check firewall:**
   - Ensure WebSocket ports (443, 80) are not blocked
   - Check if your hosting provider blocks WebSockets
   - Try from a different network

3. **Browser console test:**
   ```javascript
   // Open browser console and run:
   var pusher = new Pusher('YOUR_APP_KEY', {
       cluster: 'YOUR_CLUSTER'
   });
   pusher.connection.bind('connected', function() {
       console.log('Connected to Pusher!');
   });
   pusher.connection.bind('error', function(err) {
       console.error('Pusher error:', err);
   });
   ```

## Common Issues

### Issue: "Invalid key" error
**Solution:** Double-check your PUSHER_APP_KEY in .env matches your Pusher dashboard

### Issue: "App not found" error
**Solution:** Verify PUSHER_APP_ID is correct

### Issue: "Cluster mismatch" error
**Solution:** Ensure PUSHER_APP_CLUSTER matches your Pusher app's cluster

### Issue: Messages not appearing in real-time
**Solution:** 
1. Check browser console for errors
2. Verify `BROADCAST_CONNECTION=pusher` in .env
3. Clear config: `php artisan config:clear`
4. Rebuild assets: `npm run build`

### Issue: "401 Unauthorized" on private channels
**Solution:**
1. Ensure user is authenticated
2. Check `routes/channels.php` authorization
3. Verify CSRF token is being sent

## Quick Diagnostic Commands

```bash
# Check current broadcast driver
php artisan tinker
>>> config('broadcasting.default')

# Check Pusher config
>>> config('broadcasting.connections.pusher')

# Test broadcasting
>>> broadcast(new App\Events\MessageSent(App\Models\Message::first()));

# Check if BroadcastServiceProvider is loaded
>>> app()->getProviders('App\Providers\BroadcastServiceProvider')

# View routes
php artisan route:list --path=broadcasting
```

## Still Having Issues?

1. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Check browser console:**
   - Open DevTools (F12)
   - Look for JavaScript errors
   - Check Network tab for failed requests

3. **Test without WebSockets:**
   - Set `BROADCAST_CONNECTION=log`
   - Messages will be logged instead of broadcast
   - Check `storage/logs/laravel.log` to see if events are firing

4. **Verify Pusher status:**
   - Check https://status.pusher.com/
   - Ensure Pusher services are operational

## Working Without Real-time

The chat system will still function without real-time updates:

- ✅ Send messages
- ✅ Upload files
- ✅ Add reactions
- ✅ Reply to messages
- ✅ Delete messages
- ✅ Search messages

Users just need to refresh the page to see new messages.

To enable auto-refresh without WebSockets, you could add:

```javascript
// In message-list.blade.php
setInterval(() => {
    Livewire.dispatch('messageSent'); // Triggers refresh
}, 5000); // Refresh every 5 seconds
```

But this is not recommended for production as it increases server load.
