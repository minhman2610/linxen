#!/bin/bash

SERVER_IP=174.138.26.231
SERVER_PATH=/var/www/linxen

echo "🚀 Deploy LINXEN via RSYNC..."

# ===============================
# 1. Sync code (LOCAL → SERVER)
# ===============================
rsync -avz --delete \
  --exclude=".git" \
  --exclude="node_modules" \
  --exclude="vendor" \
  --exclude=".env" \
  --exclude="storage" \
  ./ root@$SERVER_IP:$SERVER_PATH/

# ===============================
# 2. Run server-side commands
# ===============================
ssh root@$SERVER_IP << EOF
  echo "🧹 Clear Laravel cache..."
  cd $SERVER_PATH
  php artisan route:clear
  php artisan config:clear
  php artisan view:clear
  php artisan cache:clear

  echo "🔐 Fix permissions..."
  chown -R www-data:www-data storage bootstrap/cache
  chmod -R 775 storage bootstrap/cache

  echo "✅ Deploy done!"
EOF

