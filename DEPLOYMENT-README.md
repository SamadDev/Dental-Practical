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

## Complete Deployment Instructions

### Quick Reference: Deploy Everything

When you have changes ready, run these steps in order:

#### Step 1: Commit and push to GitHub
```bash
cd /Users/samad/Dental-Practical
git add .
git commit -m "Your commit message here"
git push origin main
```

#### Step 2: Frontend auto-deploys to GitHub Pages
The GitHub Pages workflow automatically triggers when you push to `main`.
- Workflow file: `.github/workflows/deploy-frontend.yml`
- What it does: Builds the frontend from `frontend/` and publishes to GitHub Pages
- Live URL: `https://samaddev.github.io/Dental-Practical/#/home`
- Status: Check https://github.com/SamadDev/Dental-Practical/actions

**Important**: The GitHub Pages workflow runs automatically on every push to `main`. Wait for the "Deploy frontend to GitHub Pages" workflow to complete (usually 30-60 seconds).

#### Step 3: Deploy backend to remote server
From the backend folder on your local machine:

```bash
cd backend
php vendor/bin/envoy run deploy
```

This deploys the Laravel backend to `dental@176.9.120.84:/home/dental/public_html`

#### Step 4: Seed database (if needed)
```bash
cd backend
php vendor/bin/envoy run seed
```

### Individual Deploy Commands

#### Frontend deployment
Frontend is automatic via GitHub Pages workflow when you push to `main`.

To manually verify the build locally:
```bash
cd frontend
npm run build
npm run preview  # preview at http://localhost:4173/Dental-Practical/#/home
```

#### Backend deployment
```bash
cd backend
php vendor/bin/envoy run deploy
```

#### Seed data (development/testing)
```bash
cd backend
php vendor/bin/envoy run seed
```

### GitHub Pages Workflow

**File**: `.github/workflows/deploy-frontend.yml`

**Workflow details**:
- Triggers on: push to `main` branch or manual workflow dispatch
- Runs: Frontend build from `frontend/` folder
- Publishes: `frontend/dist/` to GitHub Pages under `/Dental-Practical/`
- URL: https://samaddev.github.io/Dental-Practical/

**Current workflow**:
```yaml
name: Deploy frontend to GitHub Pages

on:
  push:
    branches: [main]
  workflow_dispatch:

permissions:
  contents: read
  pages: write
  id-token: write

concurrency:
  group: "pages"
  cancel-in-progress: true

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Set up Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 20

      - name: Install dependencies
        working-directory: frontend
        run: npm install

      - name: Build frontend
        working-directory: frontend
        env:
          VITE_API_BASE: ${{ vars.VITE_API_BASE || 'https://dental.smartvisioniq.com/api/v1' }}
        run: npm run build

      - name: Configure Pages
        uses: actions/configure-pages@v5

      - name: Upload Pages artifact
        uses: actions/upload-pages-artifact@v3
        with:
          path: frontend/dist

  deploy:
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    runs-on: ubuntu-latest
    needs: build
    steps:
      - name: Deploy to GitHub Pages
        id: deployment
        uses: actions/deploy-pages@v4
```

**To check deployment status**:
1. Go to: https://github.com/SamadDev/Dental-Practical/actions
2. Look for "Deploy frontend to GitHub Pages" workflow
3. Check the latest run status (should show ✅ for success)
4. Visit live site: https://samaddev.github.io/Dental-Practical/#/home

## Notes for future AI or teammates

- The frontend is static and hosted on GitHub Pages.
- The backend is the dynamic Laravel app and must stay on the server.
- Envoy deploys the backend from GitHub to the remote host.
- The backend and frontend are separate deployment layers and should not be treated as a single monolith.
- Always ensure the frontend production API base points to the live server URL, not localhost.
- The server user must be `dental`, and the host must be `176.9.120.84`.

### For AI Assistants: Standard Deployment Flow

When the user asks to "deploy", "send to GitHub", "update production", or similar:

1. **Commit and push changes to GitHub main branch**:
   ```bash
   cd /Users/samad/Dental-Practical
   git add .
   git commit -m "Describe changes"
   git push origin main
   ```

2. **Frontend automatically deploys**:
   - The GitHub Pages workflow (`.github/workflows/deploy-frontend.yml`) triggers automatically
   - Wait ~30-60 seconds for the workflow to complete
   - Verify at: https://github.com/SamadDev/Dental-Practical/actions
   - Live frontend: https://samaddev.github.io/Dental-Practical/#/home

3. **Deploy backend if changed**:
   ```bash
   cd backend
   php vendor/bin/envoy run deploy
   ```
   - This uploads backend code to remote server
   - Server: `dental@176.9.120.84`
   - Path: `/home/dental/public_html`

4. **Verify both are working**:
   - Frontend: Open https://samaddev.github.io/Dental-Practical/#/home
   - Backend: Check that API is responding at your configured API endpoint
   - If there are issues, check the GitHub Actions logs or Envoy output

## Current verified status

The following commands were successfully run during verification:

```bash
cd /Users/samad/Dental-Practical/frontend && npm run build
cd /Users/samad/Dental-Practical/backend && php vendor/bin/envoy run deploy
cd /Users/samad/Dental-Practical/backend && php vendor/bin/envoy run seed
```

The frontend build succeeded and the backend deployment + seeding tasks completed successfully.
