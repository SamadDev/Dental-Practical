#!/bin/bash
# Run this on the production server (where dental.smartvisioniq.com is hosted)
# to apply the CORS fix + syntax error fix without needing a full deploy.

set -e

# Path to the Laravel app on the server — adjust if yours is different
APP_PATH="/var/www/dental-practical/backend"
# Or sometimes: APP_PATH="/home/youruser/public_html/backend"
# Or: APP_PATH="/usr/share/nginx/html/backend"

if [ ! -d "$APP_PATH" ]; then
  echo "❌ APP_PATH does not exist: $APP_PATH"
  echo "Edit this script and set APP_PATH to your backend directory."
  exit 1
fi

cd "$APP_PATH" || exit 1

echo "→ Backing up files..."
cp routes/api.php routes/api.php.bak.$(date +%s)
cp config/cors.php config/cors.php.bak.$(date +%s)

echo "→ Fixing double-escaped backslashes in routes/api.php..."
# Fix the syntax error: \\Illuminate -> \Illuminate and \\Throwable -> \Throwable
# These were created when someone double-escaped them in the source.
sed -i 's|\\\\Illuminate|\\Illuminate|g; s|\\\\Throwable|\\Throwable|g' routes/api.php

echo "→ Adding smartvisioniq.com to CORS allowed origins..."
# Insert the new pattern before the closing ] of allowed_origins_patterns
if ! grep -q "smartvisioniq" config/cors.php; then
  sed -i "/'#\^https?:\\/\\/localhost/d" config/cors.php
  sed -i "/'#\^https?:\\/\\/localhost/a\\        '#^https?:\\/\\/([a-z0-9-]+\\.)?smartvisioniq\\\\.com\$#'," config/cors.php
fi

echo "→ Clearing config cache..."
php artisan config:clear
php artisan config:cache

echo ""
echo "✅ Done. Now test the preflight:"
echo "   curl -I -X OPTIONS https://dental.smartvisioniq.com/api/v1/login \\"
echo "        -H 'Origin: https://dental.smartvisioniq.com' \\"
echo "        -H 'Access-Control-Request-Method: POST' \\"
echo "        -H 'Access-Control-Request-Headers: content-type,authorization'"
echo ""
echo "   You should see:  HTTP/2 200"
echo "   And headers:     access-control-allow-origin: https://dental.smartvisioniq.com"