@setup
    $repository = 'git@github.com:SamadDev/Dental-Practical.git';
    $branch = 'main';
    $deployPath = '/home/dental/dental-practismart';
    $backendPath = $deployPath . '/backend';
    $frontendPath = $deployPath . '/frontend';
@endsetup

@servers(['web' => 'dental@176.9.120.84'])

@task('deploy', ['on' => 'web'])
    cd {{ $deployPath }}
    
    # Pull latest
    git fetch origin
    git reset --hard origin/{{ $branch }}
    
    # Backend
    cd {{ $backendPath }}
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # Migrations & seed
    php artisan migrate --force
    php artisan db:seed --force || true
    
    # Cache
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Storage link
    php artisan storage:link
    
    # Frontend
    cd {{ $frontendPath }}
    npm ci
    npm run build
    
    # Permissions
    chown -R dental:dental {{ $deployPath }}
    chmod -R 775 {{ $backendPath }}/storage {{ $backendPath }}/bootstrap/cache
    
    # Restart services
    sudo systemctl reload dental-backend || sudo systemctl restart dental-backend
    sudo nginx -t && sudo systemctl reload nginx
    
    echo "✅ Deploy complete"
@endtask

@task('rollback', ['on' => 'web'])
    cd {{ $deployPath }}
    git fetch origin
    git reset --hard HEAD~1
    @task('deploy')
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