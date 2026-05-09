#!/usr/bin/env bash

# If WWWUSER is not set, or is 0, do nothing.
if [ -z "$WWWUSER" ] || [ "$WWWUSER" -eq 0 ]; then
    echo "Running as root. Not changing user."
else
    # Change sail user's UID to match host user's UID
    if [ -d "/var/www/html" ]; then
        usermod -u "$WWWUSER" sail
        mkdir -p /var/www/html/storage/logs
        touch /var/www/html/storage/logs/php-fpm.log
        touch /var/www/html/storage/logs/php-fpm-access.log
        # Change ownership of the application directory
        chown -R sail:sail /var/www/html
    fi
fi

# Execute the CMD as the sail user
exec gosu sail "$@"
