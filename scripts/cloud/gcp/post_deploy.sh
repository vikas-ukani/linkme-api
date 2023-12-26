#!/bin/bash
# Get the script arguments
options=$*

# An array with all the arguments
arguments=($options)

# Loop index
index=0

for argument in $options
	do
	# Incrementing index
	index=$((index+1))

	# The conditions
	case $argument in
		--env) ENV=${arguments[index]};;
	esac
done

if [ -z "${ENV}" ]; then
	case ${BRANCH_NAME} in
		'master')
			ENV="staging";;
		'staging')
			ENV="staging";;
		'beta')
			ENV="beta";;
		*)
			ENV="dev";;
	esac
fi

echo -e "\n0 - Sourcing environment variables: ${ENV}"
# Source the environment variables
. /var/www/html/.env

echo -e "\n1 - Updating environment: ${ENV}"
#cp ".env.$ENV" .env
#cp storage/oauth-private.${ENV}.key storage/oauth-private.key
#cp storage/oauth-public.${ENV}.key storage/oauth-public.key

# Going into maintenance mode
echo -e "\n2 - Going into maintenance mode"
php artisan down

# Applying migrations and optimizing application
echo -e "\n3 - Discovering packages, applying migrations and optimizing application"

echo -e "\n3a - Switcing DB IPs"
sed -i "s~DB_HOST=127.0.0.1~DB_HOST=$DB_HOST_DEPLOY~g" .env

php artisan package:discover
# Doing this here as there are soem issues when deploying to build environment
# php artisan l5-swagger:generate
php artisan migrate --force
php artisan telescope:publish
# php artisan horizon:install
# php artisan horizon:assets
# php artisan prequel:update
php artisan clear
php artisan view:clear
php artisan view:cache
php artisan storage:link
# php artisan optimize

echo -e "\n4 - Exiting maintenance mode"
# Seems there's a laravel bug that requires an extra config clear
php artisan up

echo -e "\n5 - Switcing redis and queue drivers and DB IPs"
sed -i "s~CACHE_DRIVER=file~CACHE_DRIVER=redis~g" .env
sed -i "s~QUEUE_CONNECTION=sync~QUEUE_CONNECTION=redis~g" .env
sed -i "s~DB_HOST=$DB_HOST_DEPLOY~DB_HOST=$DB_HOST_ACTIVE~g" .env

echo -e "\n6 - Ensuring crontab for laravel commands"
bash ./scripts/ensure_scheduled_commands.sh

echo -e "\nDeploy Done!"
exit 0
