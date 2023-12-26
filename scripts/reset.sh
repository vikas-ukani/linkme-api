#!/bin/bash

set -e
set -x

echo "Resetting..."
php artisan migrate:reset
bash scripts/install.sh
#bash scripts/post_deploy.sh
