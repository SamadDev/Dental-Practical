# Deployment Guide

This project is deployed in a split architecture:

- Frontend: GitHub Pages
- Backend: Laravel app running on a remote Linux server
- Deployment tool: Laravel Envoy

## Architecture

- Frontend repository: GitHub repository
- Frontend hosting: GitHub Pages
- Backend repo: same GitHub repository
- Backend deployment target: remote server at `176.9.120.84`
- Backend user: `dental`
- Backend deploy path: `/home/dental/public_html`
- Source repo path on server: `/home/dental/dental-practismart`

## Current deployment setup

### Frontend
The frontend is built with Vite and is configured for GitHub Pages.

Relevant file:
- `frontend/vite.config.js`

Important setting:

```js
base: '/Dental-Practical/'
```

This means the frontend is expected to be published under the GitHub Pages project path `Dental-Practical`.

### Backend
The backend is a Laravel app deployed to the server with Envoy.

Relevant file:
- `backend/Envoy.blade.php`

Current configured server:

```php
@servers(['web' => 'dental@176.9.120.84'])
```

Deployment folder:

```php
$repoPath = '/home/dental/dental-practismart';
$backendPath = '/home/dental/public_html';
```

## How deployment works

### Backend deploy
The backend deploy script:

1. pulls the latest code from GitHub
2. syncs the `backend/` folder into `/home/dental/public_html`
3. installs Composer dependencies
4. runs migrations
5. runs seeders
6. caches Laravel config/routes/views
7. sets permissions on storage and cache

### Frontend deploy
The frontend deploy is handled through GitHub Pages Actions:

1. install Node dependencies
2. run `npm run build`
3. publish the `dist` folder to GitHub Pages

## Important deployment rules

### 1. Fix the server username
The server target must use the correct SSH user and host.

Use:

```php
@servers(['web' => 'dental@176.9.120.84'])
```

Not:

```php
@servers(['web' => 'dentail'])
```

### 2. The frontend must use the live backend URL
The frontend should not use `localhost` in production.

Use a real live backend URL, for example:

```env
VITE_API_BASE=https://176.9.120.84
```

or a custom domain if it is configured.

### 3. Server Laravel environment must be on the remote server
The production backend must have a working `.env` file in `/home/dental/public_html`.

It should include:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

## Deploy commands

### Backend
From the local repo:

```bash
cd backend
php vendor/bin/envoy run deploy
```

### Seed data

```bash
cd backend
php vendor/bin/envoy run seed
```

## GitHub Pages workflow example

Create `.github/workflows/deploy-frontend.yml` with:

```yaml
name: Deploy frontend

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      pages: write
      id-token: write

    steps:
      - uses: actions/checkout@v4

      - uses: actions/setup-node@v4
        with:
          node-version: 20

      - name: Install dependencies
        working-directory: frontend
        run: npm ci

      - name: Build frontend
        working-directory: frontend
        run: npm run build

      - name: Upload artifact
        uses: actions/upload-pages-artifact@v3
        with:
          path: frontend/dist

      - name: Deploy to GitHub Pages
        uses: actions/deploy-pages@v4
```

## Notes for future AI or teammates

- The frontend is static and hosted on GitHub Pages.
- The backend is the dynamic Laravel app and must stay on the server.
- Envoy deploys the backend from GitHub to the remote host.
- The backend and frontend are separate deployment layers and should not be treated as a single monolith.
- Always ensure the frontend production API base points to the live server URL, not localhost.
- The server user must be `dental`, and the host must be `176.9.120.84`.

## Current verified status

The following commands were successfully run during verification:

```bash
cd /Users/samad/Dental-Practical/frontend && npm run build
cd /Users/samad/Dental-Practical/backend && php vendor/bin/envoy run deploy
cd /Users/samad/Dental-Practical/backend && php vendor/bin/envoy run seed
```

The frontend build succeeded and the backend deployment + seeding tasks completed successfully.
