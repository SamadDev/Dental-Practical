@setup
    $branch = 'main';
    $repoPath = '/home/dental/dental-practismart';
    $backendPath = '/home/dental/public_html';
@endsetup

@servers(['web' => 'dentail'])

@task('deploy', ['on' => 'web'])
    # Pull latest into working copy
    cd {{ $repoPath }}
    git fetch origin
    git reset --hard origin/{{ $branch }}

    # Sync backend into web root (keeps live .env and storage)
    rsync -a --delete \
        --exclude '.env' \
        --exclude 'storage' \
        --exclude '.git' \
        {{ $repoPath }}/backend/ {{ $backendPath }}/

    # Dependencies
    cd {{ $backendPath }}
    {{ $backendPath }}/../bin/composer install --no-dev --optimize-autoloader --no-interaction

    # Migrations & seed
    php artisan migrate --force
    php artisan db:seed --force || true

    # Cache
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Storage link
    php artisan storage:link || true

    # Permissions
    chmod -R 775 {{ $backendPath }}/storage {{ $backendPath }}/bootstrap/cache

    echo "✅ Deploy complete"
@endtask

@task('rollback', ['on' => 'web'])
    cd {{ $repoPath }}
    git reset --hard HEAD~1

    rsync -a --delete \
        --exclude '.env' \
        --exclude 'storage' \
        --exclude '.git' \
        {{ $repoPath }}/backend/ {{ $backendPath }}/

    cd {{ $backendPath }}
    {{ $backendPath }}/../bin/composer install --no-dev --optimize-autoloader --no-interaction
    php artisan migrate --force
    php artisan db:seed --force || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan storage:link || true
    chmod -R 775 {{ $backendPath }}/storage {{ $backendPath }}/bootstrap/cache
    echo "✅ Rollback & redeploy complete"
@endtask

@task('logs', ['on' => 'web'])
    tail -f {{ $backendPath }}/storage/logs/laravel.log
@endtask

@task('queue:restart', ['on' => 'web'])
    cd {{ $backendPath }}
    php artisan queue:restart
@endtask

@task('migrate', ['on' => 'web'])
    cd {{ $backendPath }}
    php artisan migrate --force
@endtask

@task('seed', ['on' => 'web'])
    cd {{ $backendPath }}
    php artisan db:seed --force
@endtask

@task('cache:clear', ['on' => 'web'])
    cd {{ $backendPath }}
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
@endtask
