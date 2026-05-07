@servers(['prod' => 'root@100.117.45.2'])

@setup
    $repository = 'https://github.com/decaller/kutubio.git';
    $app_dir = '/root/kutubio';
    $app_url = 'https://kutubio.insantaqwa.org';
@endsetup

@task('deploy', ['on' => 'prod'])
    if [ ! -d {{ $app_dir }} ]; then
        git clone {{ $repository }} {{ $app_dir }}
    fi

    cd {{ $app_dir }}
    git pull origin main

    # Setup .env for production
    if [ ! -f .env ]; then
        cp .env.example .env
        sed -i "s|APP_URL=http://localhost|APP_URL={{ $app_url }}|g" .env
        sed -i 's/APP_ENV=local/APP_ENV=production/g' .env
        sed -i 's/APP_DEBUG=true/APP_DEBUG=false/g' .env
        
        # Database & Redis for Sail
        sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=pgsql/g' .env
        sed -i 's/DB_HOST=127.0.0.1/DB_HOST=pgsql/g' .env
        sed -i 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g' .env

        # Production User setup for Sail
        echo "WWWUSER=$(id -u)" >> .env
        echo "WWWGROUP=$(id -g)" >> .env
    fi

    # Build and start containers
    # WWWUSER and WWWGROUP are needed for Sail's build process
    export WWWUSER=$(id -u)
    export WWWGROUP=$(id -g)
    docker compose up -d --build
    
    # Run production tasks inside the built container
    docker compose exec -T laravel.test composer install --no-interaction --prefer-dist --optimize-autoloader
    docker compose exec -T laravel.test php artisan key:generate --force
    docker compose exec -T laravel.test php artisan migrate --force
    docker compose exec -T laravel.test php artisan optimize
    docker compose exec -T laravel.test php artisan horizon:terminate
@endtask

@task('status', ['on' => 'prod'])
    cd {{ $app_dir }}
    docker compose ps
@endtask
