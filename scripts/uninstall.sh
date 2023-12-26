#!/bin/bash

set -e
set -x

env=$(php artisan env | cut -d':' -f 2)
confirm=true;
if [ $env = 'production' ]; then
    read -p "CAUTION: You're running this script in a PRODUCTION environment. Are you sure? [Yes/No]" confirm
fi

echo -e "Running uninstall in ${env}"
if [ ${confirm} = true ] || [ ${confirm} = 'y'] || [ ${confirm} = 'Y' ] || [ ${confirm} = 'Yes' ] || [ ${confirm} = 'yes' ]; then
    echo "Emptying databases..."
    php artisan migrate:reset
fi
