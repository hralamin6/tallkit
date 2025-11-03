# Auto-Subscribe Testing Guide

## Current Status
✅ JavaScript compiled successfully
✅ VAPID keys configured
✅ Service Worker exists
✅ CSRF token present
✅ All API routes configured

## How to Test Auto-Subscribe

### 1. Reset Your Browser State (First Time Test)
Open browser console (F12) and run:
```javascript
resetAutoSubscribe()
```

### 2. Clear Browser Data
- Go to browser settings → Clear browsing data
- Clear "Site Settings" and "Cookies"
- OR use Incognito/Private mode

### 3. Visit the Application
Navigate to: `http://your-app-url/app`

### 4. Check Console Logs
You should see these logs in order:
```
🎬 DOM loaded, initializing push manager...
✅ Service Worker ready
✅ VAPID key loaded
✅ Push notification manager initialized
✅ Push manager initialized, will attempt auto-subscribe in 2 seconds...
🚀 Starting auto-subscribe...
📋 Current notification permission: default (or granted)
🔔 Requesting notification permission for auto-subscribe...
📱 Browser subscribed: [endpoint]...
✅ Server saved subscription: {success: true, message: "..."}
✅ Auto-subscribed successfully
```

### 5. Permission Prompt
- A browser notification permission prompt should appear after 2 seconds
- Click "Allow" to subscribe

## Debugging Commands

### Check Current Subscription Status
```javascript
await checkPushStatus()
```

### Manually Subscribe
```javascript
await subscribeToPush()
```

### Manually Unsubscribe
```javascript
await unsubscribeFromPush()
```

### Check localStorage State
```javascript
localStorage.getItem('push_auto_subscribe_attempted')
localStorage.getItem('push_subscribed_at')
```

### Reset and Try Again
```javascript
resetAutoSubscribe()
// Then reload the page
location.reload()
```

## Common Issues & Solutions

### Issue: No permission prompt appears
**Solution:**
1. Check console for errors
2. Verify VAPID keys are set: Visit `/api/push/vapid-key`
3. Ensure you're using HTTPS (or localhost)
4. Check if permission was already denied

### Issue: "Push manager not ready"
**Solution:**
1. Ensure service worker is registered
2. Check if `/sw.js` is accessible
3. Clear service worker cache

### Issue: Already attempted message
**Solution:**
```javascript
resetAutoSubscribe()
location.reload()
```

### Issue: Permission denied
**Solution:**
1. Reset browser permissions for the site
2. Chrome: Settings → Privacy → Site Settings → Notifications
3. Remove your site and try again

## Expected Behavior

### First Visit (permission = default)
- Wait 2 seconds after page load
- Permission prompt appears
- User clicks "Allow"
- Subscription created and saved
- localStorage marks attempt as successful

### Subsequent Visits (already subscribed)
- Auto-subscribe checks status
- Finds existing subscription
- Skips prompting
- Marks as successful

### Permission Already Granted
- Subscribes silently without prompting
- Creates subscription automatically

### Permission Denied
- Skips auto-subscribe
- Marks as attempted
- Won't prompt again

## Verify Subscription Works

### Send Test Notification
1. Login to your app
2. Go to `/app/notifications`
3. Use the notification center to send a test

### Check Backend
```bash
php artisan tinker
>>> \App\Models\User::first()->pushSubscriptions()->count()
```

## Production Checklist

- [ ] HTTPS enabled (required for push notifications)
- [ ] VAPID keys set in production .env
- [ ] Service worker accessible at `/sw.js`
- [ ] All push API routes working
- [ ] Browser notifications enabled on user devices

