#!/bin/bash
# Initial server setup script
# Run ONCE on the server as root or dental user with sudo

set -e

echo "=== Dental Practi-Smart Server Setup ==="

# Update system
apt-get update && apt-get upgrade -y

# Install required packages
apt-get install -y \
    nginx \
    php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-common \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-sqlite3 \
    php8.3-gd \
    php8.3-intl \
    composer \
    nodejs \
    npm \
    git \
    certbot \
    python3-certbot-nginx \
    sqlite3 \
    unzip

# Verify PHP version
php -v

# Verify Node version
node -v
npm -v

# Create deploy user if not exists
if ! id "dental" &>/dev/null; then
    useradd -m -s /bin/bash dental
    usermod -aG sudo dental
    echo "dental ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx, /bin/systemctl reload dental-backend, /bin/systemctl restart dental-backend" | tee /etc/sudoers.d/dental-deploy
fi

# Setup SSH key for dental user (for GitHub Actions)
mkdir -p /home/dental/.ssh
chmod 700 /home/dental/.ssh
# TODO: Add GitHub Actions public key to /home/dental/.ssh/authorized_keys
# ssh-ed25519 AAAAC3... github-actions@deploy

# Install Laravel installer globally (optional)
sudo -u dental composer global require laravel/installer --no-interaction

# Setup Nginx
cp /home/dental/dental-practismart/deploy/nginx.conf /etc/nginx/sites-available/dental-practismart
ln -sf /etc/nginx/sites-available/dental-practismart /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# Setup systemd service
cp /home/dental/dental-practismart/deploy/dental-backend.service /etc/systemd/system/dental-backend.service
systemctl daemon-reload
systemctl enable --now dental-backend

# Setup SSL with Let's Encrypt (run after DNS points to server)
# certbot --nginx -d your-domain.com -d www.your-domain.com

# Setup log rotation
cat > /etc/logrotate.d/dental-practismart << 'EOF'
/home/dental/dental-practismart/backend/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 dental dental
    sharedscripts
    postrotate
        systemctl reload dental-backend > /dev/null 2>&1 || true
    endscript
}
EOF

echo "=== Server setup complete ==="
echo ""
echo "Next steps:"
echo "1. Add GitHub Actions SSH public key to /home/dental/.ssh/authorized_keys"
echo "2. Add repository secrets in GitHub: SSH_PRIVATE_KEY (your private key)"
echo "3. Update server_name in /etc/nginx/sites-available/dental-practismart"
echo "4. Run: certbot --nginx -d your-domain.com"
echo "5. Push to main branch to trigger first deploy"