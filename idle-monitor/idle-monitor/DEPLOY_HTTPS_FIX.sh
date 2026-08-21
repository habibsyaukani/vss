#!/bin/bash
# =============================================================================
# DEPLOY HTTPS FIX - VAMS Login via https://vams.gpe.web.id
# Jalankan di SERVER VAMS (10.2.2.18 / 103.130.6.115)
# =============================================================================

echo "======================================================"
echo " STEP 1: Update .env (APP_URL, SESSION settings)"
echo "======================================================"

# Masuk ke direktori Laravel VAMS (sesuaikan path!)
# cd /var/www/vams   # atau path Laravel di server kamu
# cd /home/user/vams # sesuaikan

# Backup .env dulu
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update APP_URL
sed -i "s|APP_URL=.*|APP_URL=https://vams.gpe.web.id|g" .env

# Hapus SESSION_DOMAIN & SESSION_SECURE_COOKIE lama (jika ada) lalu tambah yang baru
sed -i '/^SESSION_DOMAIN=/d' .env
sed -i '/^SESSION_SECURE_COOKIE=/d' .env

# Tambahkan setelah SESSION_LIFETIME
sed -i '/^SESSION_LIFETIME/a SESSION_DOMAIN=vams.gpe.web.id\nSESSION_SECURE_COOKIE=true' .env

echo ">>> .env updated:"
grep -E "^APP_URL|^SESSION" .env

echo ""
echo "======================================================"
echo " STEP 2: Clear semua cache Laravel"
echo "======================================================"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "======================================================"
echo " STEP 3: Verifikasi TrustProxies.php"
echo "======================================================"
echo ">>> Cek isi TrustProxies.php:"
grep "proxies" app/Http/Middleware/TrustProxies.php

echo ""
echo ">>> Pastikan ada: protected \$proxies = '*';"
echo ">>> Jika belum, edit manual:"
echo "    nano app/Http/Middleware/TrustProxies.php"
echo "    Ubah: protected \$proxies;"
echo "    Menjadi: protected \$proxies = '*';"

echo ""
echo "======================================================"
echo " STEP 4: Test config nginx Ubuntu"
echo "======================================================"
echo ">>> Jalankan di server NGINX UBUNTU:"
echo "    sudo nginx -t"
echo "    sudo systemctl reload nginx"

echo ""
echo "======================================================"
echo " STEP 5: Test Login"
echo "======================================================"
echo ">>> Buka browser: https://vams.gpe.web.id"
echo ">>> Buka DevTools → Network → perhatikan:"
echo "    - Request POST /login harus 302 redirect (bukan 419)"
echo "    - Cookie 'laravel_session' harus ada flag Secure=true, SameSite=Lax"
echo "    - Tidak ada error CSRF (419 Too Many Requests)"

echo ""
echo "======================================================"
echo " TROUBLESHOOTING jika masih gagal:"
echo "======================================================"
echo "1. Cek log Laravel:"
echo "   tail -f storage/logs/laravel.log"
echo ""
echo "2. Pastikan nginx set header ini (cek /etc/nginx/sites-available/vams.gpe.web.id):"
echo "   proxy_set_header X-Forwarded-Proto https;"
echo "   proxy_set_header X-Forwarded-Port 443;"
echo "   proxy_set_header Host \$host;"
echo ""
echo "3. Jika Docker, pastikan APP_URL di .env container sudah di-rebuild:"
echo "   docker-compose down && docker-compose up -d"
echo "   docker exec -it <container> php artisan config:clear"
