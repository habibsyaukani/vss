#!/bin/bash

#######################################################
# IDLE MONITOR - SERVER DEPLOYMENT SCRIPT
# Deploy to: 10.2.2.18
# Database: Docker MySQL
# Code: GitHub Repository
#######################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  IDLE MONITOR - DEPLOYMENT SCRIPT${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Configuration
SERVER_IP="10.2.2.18"
SERVER_USER="root"  # Change if different
APP_DIR="/var/www/idle-monitor"
GITHUB_REPO="https://github.com/habibsyaukani/vss.git"
BRANCH="main"  # or master

# Step 1: Check if SSH connection is available
echo -e "${YELLOW}[1/10] Checking SSH connection...${NC}"
if ssh -o ConnectTimeout=5 ${SERVER_USER}@${SERVER_IP} "echo 'SSH OK'" &> /dev/null; then
    echo -e "${GREEN}✓ SSH connection successful${NC}"
else
    echo -e "${RED}✗ Cannot connect to ${SERVER_IP}${NC}"
    echo -e "${RED}Please check:"
    echo -e "  1. Server is running"
    echo -e "  2. SSH is enabled"
    echo -e "  3. Firewall allows SSH (port 22)"
    echo -e "  4. You have SSH key or password access${NC}"
    exit 1
fi

# Step 2: Install dependencies on server
echo -e "${YELLOW}[2/10] Installing dependencies on server...${NC}"
ssh ${SERVER_USER}@${SERVER_IP} << 'ENDSSH'
# Update system
apt-get update -qq

# Install required packages
apt-get install -y \
    git \
    curl \
    wget \
    unzip \
    ca-certificates \
    gnupg \
    lsb-release \
    software-properties-common

# Install Docker
if ! command -v docker &> /dev/null; then
    echo "Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    systemctl enable docker
    systemctl start docker
    rm get-docker.sh
else
    echo "Docker already installed"
fi

# Install Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo "Installing Docker Compose..."
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
else
    echo "Docker Compose already installed"
fi

# Install PHP 8.1 and extensions
if ! command -v php &> /dev/null; then
    echo "Installing PHP 8.1..."
    add-apt-repository ppa:ondrej/php -y
    apt-get update -qq
    apt-get install -y \
        php8.1-fpm \
        php8.1-cli \
        php8.1-mysql \
        php8.1-redis \
        php8.1-xml \
        php8.1-curl \
        php8.1-mbstring \
        php8.1-zip \
        php8.1-bcmath \
        php8.1-gd \
        php8.1-intl
else
    echo "PHP already installed"
fi

# Install Composer
if ! command -v composer &> /dev/null; then
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
else
    echo "Composer already installed"
fi

# Install Nginx
if ! command -v nginx &> /dev/null; then
    echo "Installing Nginx..."
    apt-get install -y nginx
    systemctl enable nginx
    systemctl start nginx
else
    echo "Nginx already installed"
fi

echo "✓ Dependencies installed"
ENDSSH
echo -e "${GREEN}✓ Dependencies installed${NC}"

# Step 3: Setup Docker MySQL
echo -e "${YELLOW}[3/10] Setting up Docker MySQL...${NC}"
ssh ${SERVER_USER}@${SERVER_IP} << 'ENDSSH'
mkdir -p /opt/idle-monitor-docker
cat > /opt/idle-monitor-docker/docker-compose.yml << 'EOF'
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: idle-monitor-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: IdleMonitor@2026!
      MYSQL_DATABASE: vss
      MYSQL_USER: idle_user
      MYSQL_PASSWORD: IdleUser@2026!
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    command: --default-authentication-plugin=mysql_native_password

  redis:
    image: redis:7-alpine
    container_name: idle-monitor-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

volumes:
  mysql_data:
  redis_data:
EOF

cd /opt/idle-monitor-docker
docker-compose up -d
sleep 10  # Wait for MySQL to start
echo "✓ Docker containers started"
ENDSSH
echo -e "${GREEN}✓ Docker MySQL & Redis running${NC}"

# Step 4: Clone repository from GitHub
echo -e "${YELLOW}[4/10] Cloning repository from GitHub...${NC}"
echo -e "${BLUE}GitHub Repo: ${GITHUB_REPO}${NC}"
echo -e "${BLUE}Branch: ${BRANCH}${NC}"

ssh ${SERVER_USER}@${SERVER_IP} << ENDSSH
# Remove old directory if exists
if [ -d "${APP_DIR}" ]; then
    echo "Removing old installation..."
    rm -rf ${APP_DIR}
fi

# Clone repository
echo "Cloning from GitHub..."
git clone -b ${BRANCH} ${GITHUB_REPO} ${APP_DIR}
cd ${APP_DIR}

echo "✓ Repository cloned"
ENDSSH
echo -e "${GREEN}✓ Repository cloned${NC}"

# Step 5: Setup .env file
echo -e "${YELLOW}[5/10] Setting up .env file...${NC}"
ssh ${SERVER_USER}@${SERVER_IP} << 'ENDSSH'
cd /var/www/idle-monitor
cp .env.production.example .env

# Generate APP_KEY
php artisan key:generate --force

echo "✓ .env configured"
ENDSSH
echo -e "${GREEN}✓ .env file configured${NC}"

# Step 6: Install Composer dependencies
echo -e "${YELLOW}[6/10] Installing Composer dependencies...${NC}"
ssh ${SERVER_USER}@${SERVER_IP} << 'ENDSSH'
cd /var/www/idle-monitor
composer install --no-dev --optimize-autoloader --no-interaction
echo "✓ Composer dependencies installed"
ENDSSH
echo -e "${GREEN}✓ Composer dependencies installed${NC}"

# Step 7: Run database migrations
echo -e "${YELLOW}[7/10] Running database migrations...${NC}"
ssh ${SERVER_USER}@${SERVER_IP} << 'ENDSSH'
cd /var/www/idle-monitor
php artisan migrate --force
php artisan db:seed --force
echo "✓ Database migrations completed"
ENDSSH
echo -e "${GREEN}✓ Database migrated${NC}"

# Step 8: Setup file permissions
echo -e "${YELLOW}[8/10] Setting up file permissions...${NC}"
ssh ${SERVER_USER}@${SERVER_IP} << 'ENDSSH'
cd /var/www/idle-monitor
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo "✓ Permissions set"
ENDSSH
echo -e "${GREEN}✓ File permissions configured${NC}"

# Step 9: Configure Nginx
echo -e "${YELLOW}[9/10] Configuring Nginx...${NC}"
ssh ${SERVER_USER}@${SERVER_IP} << 'ENDSSH'
cat > /etc/nginx/sites-available/idle-monitor << 'EOF'
server {
    listen 80;
    server_name 10.2.2.18;
    root /var/www/idle-monitor/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/idle-monitor /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and reload Nginx
nginx -t
systemctl reload nginx

echo "✓ Nginx configured"
ENDSSH
echo -e "${GREEN}✓ Nginx configured${NC}"

# Step 10: Setup scheduler and queue worker
echo -e "${YELLOW}[10/10] Setting up scheduler and queue worker...${NC}"
ssh ${SERVER_USER}@${SERVER_IP} << 'ENDSSH'
# Add cron job for Laravel scheduler
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/idle-monitor && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Create systemd service for queue worker
cat > /etc/systemd/system/idle-monitor-queue.service << 'EOF'
[Unit]
Description=Idle Monitor Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/idle-monitor/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable idle-monitor-queue
systemctl start idle-monitor-queue

echo "✓ Scheduler and queue worker configured"
ENDSSH
echo -e "${GREEN}✓ Scheduler and queue worker running${NC}"

# Deployment completed
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Application URL:${NC} http://10.2.2.18"
echo -e "${BLUE}Database:${NC} MySQL (Docker) on port 3306"
echo -e "${BLUE}Redis:${NC} Redis (Docker) on port 6379"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Open browser: http://10.2.2.18"
echo "2. Login with admin credentials"
echo "3. Check System Health page"
echo "4. Monitor queue worker status"
echo ""
echo -e "${BLUE}Useful Commands on Server:${NC}"
echo "  ssh root@10.2.2.18"
echo "  cd /var/www/idle-monitor"
echo "  php artisan queue:work          # Run queue manually"
echo "  docker-compose logs -f          # View Docker logs"
echo "  systemctl status idle-monitor-queue  # Check queue service"
echo "  tail -f storage/logs/laravel.log     # View app logs"
echo ""
