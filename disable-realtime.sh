#!/bin/bash

echo "🔄 Disabling real-time features temporarily..."
echo ""
echo "This will make the chat work without WebSockets."
echo "Messages will still be sent, but users need to refresh to see new messages."
echo ""

# Update .env to use log driver
if grep -q "BROADCAST_CONNECTION=" .env; then
    sed -i 's/BROADCAST_CONNECTION=.*/BROADCAST_CONNECTION=log/' .env
    echo "✅ Updated BROADCAST_CONNECTION to 'log'"
else
    echo "BROADCAST_CONNECTION=log" >> .env
    echo "✅ Added BROADCAST_CONNECTION=log to .env"
fi

# Clear config cache
php artisan config:clear

echo ""
echo "✅ Real-time features disabled!"
echo ""
echo "The chat will now work without WebSockets."
echo "To re-enable real-time, change BROADCAST_CONNECTION back to 'pusher' in .env"
