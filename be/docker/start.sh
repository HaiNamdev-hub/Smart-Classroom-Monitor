#!/bin/sh
set -e

echo "==> Tạo .env từ biến môi trường Render..."
cp -n .env.example .env 2>/dev/null || true

# Mặc định bật Reverb cho WebSockets
export BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-reverb}
export REVERB_APP_ID=${REVERB_APP_ID:-813952}
export REVERB_APP_KEY=${REVERB_APP_KEY:-wb5jfofeqpobmdzyoz0i}
export REVERB_APP_SECRET=${REVERB_APP_SECRET:-swmxg7bsv8t5vglm2rju}
export REVERB_HOST=${REVERB_HOST:-smart-classroom-monitor.onrender.com}
export REVERB_PORT=${REVERB_PORT:-443}
export REVERB_SCHEME=${REVERB_SCHEME:-https}

echo "==> Tạo APP_KEY nếu chưa có..."
php artisan key:generate --force

echo "==> Chạy database migration..."
php artisan migrate --force

echo "==> Xóa cache cũ..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "==> Tạo storage link..."
php artisan storage:link || true

echo "==> Đảm bảo quyền thư mục tạm cho Nginx upload..."
mkdir -p /tmp/nginx_client_body /var/lib/nginx/tmp/client_body 2>/dev/null || true
chown -R www-data:www-data /tmp/nginx_client_body /var/lib/nginx 2>/dev/null || true
chmod -R 777 /tmp/nginx_client_body /var/lib/nginx 2>/dev/null || true

echo "==> Khởi động PHP-FPM và Nginx qua Supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
