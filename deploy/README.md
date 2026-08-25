# Dental Practi-Smart — Deployment Guide

## Quick Start

### 1. Server Requirements
- Ubuntu 22.04/24.04 LTS
- PHP 8.3+, Node 20+, Nginx, SQLite/MySQL
- 1GB+ RAM, 10GB+ disk

### 2. One-Time Server Setup

```bash
# On your server as root:
curl -fsSL https://raw.githubusercontent.com/YOUR_USERNAME/Dental-Practical/main/deploy/server-setup.sh | bash

# Or manually run the steps in deploy/server-setup.sh
```

### 3. GitHub Repository Secrets

Go to **Settings → Secrets and variables → Actions** and add:

| Secret | Value |
|--------|-------|
| `SSH_PRIVATE_KEY` | Your private SSH key (contents of `~/.ssh/id_rsa` or similar) |

### 4. Authorize GitHub Actions on Server

```bash
# On server, add GitHub's SSH public key to dental user:
mkdir -p /home/dental/.ssh
echo "ssh-ed25519 AAAAC3... github-actions@deploy" >> /home/dental/.ssh/authorized_keys
chmod 600 /home/dental/.ssh/authorized_keys
chown -R dental:dental /home/dental/.ssh
```

> Get the public key from GitHub Actions runner output, or use a dedicated deploy key pair.

### 5. Configure Domain

Edit `/etc/nginx/sites-available/dental-practismart`:
```nginx
server_name your-domain.com;  # <-- YOUR DOMAIN
```

Then:
```bash
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d your-domain.com
```

### 6. Deploy

Push to `main` branch — GitHub Actions will:
1. Build frontend
2. SSH to server
3. Pull latest code
4. Install dependencies
5. Run migrations & seed
6. Build frontend on server
7. Restart services
8. Reload Nginx

---

## Files in `/deploy`

| File | Purpose |
|------|---------|
| `server-setup.sh` | One-time server provisioning |
| `nginx.conf` | Nginx site config |
| `dental-backend.service` | Systemd service for Laravel |

---

## Manual Deploy (Alternative)

```bash
# On server:
cd /home/dental/dental-practismart
git pull origin main

cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache

cd ../frontend
npm ci && npm run build

sudo systemctl reload dental-backend
sudo systemctl reload nginx
```

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `composer: not found` | `apt-get install composer` or use `php /usr/bin/composer` |
| `npm: not found` | `apt-get install nodejs npm` (or use NodeSource) |
| Permission denied | `chown -R dental:dental /home/dental/dental-practismart` |
| SQLite locked | Ensure single process; use `php artisan serve` only |
| 502 Bad Gateway | Check `systemctl status dental-backend` and port 8000 |

---

## Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Browser   │────▶│    Nginx    │────▶│  Frontend   │ (SPA, static files)
│             │     │  :80 / :443 │     │  /dist      │
└─────────────┘     └──────┬──────┘     └─────────────┘
                           │
                    ┌──────┴──────┐
                    │  /api/*     │
                    ▼             ▼
              ┌─────────────┐ ┌─────────────┐
              │  Laravel    │ │   Storage   │
              │  :8000      │ │  /storage   │
              └─────────────┘ └─────────────┘
```