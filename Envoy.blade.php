@servers(['prod' => 'root@100.117.45.2'])

@setup
    $repository = 'https://github.com/decaller/kutubio.git';
    $app_dir = '/root/kutubio';
@endsetup

@task('deploy', ['on' => 'prod'])
    if [ ! -d {{ $app_dir }} ]; then
        git clone {{ $repository }} {{ $app_dir }}
    fi

    cd {{ $app_dir }}
    git pull origin main

    # Ensure .env exists (this might need manual adjustment for secrets)
    if [ ! -f .env ]; then
        cp .env.example .env
        sed -i 's/APP_ENV=local/APP_ENV=production/g' .env
        sed -i 's/APP_DEBUG=true/APP_DEBUG=false/g' .env
        # Randomize secrets if needed, but usually better to manage this separately
    fi

    docker compose up -d --build
    
    # Run migrations inside the container
    docker compose exec -T laravel.test php artisan migrate --force
    docker compose exec -T laravel.test php artisan optimize
@endtask

@task('status', ['on' => 'prod'])
    cd {{ $app_dir }}
    docker compose ps
@endtask
