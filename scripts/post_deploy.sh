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
			ENV="production";;
		*)
			ENV="dev";;
	esac
fi

echo -e "\n1 - Deploying to environment: ${ENV}"
mkdir -p .backup
if [ "${ENV}" = "dev" ]; then
	echo -e "\n2a - Skipping creating backup since we're in dev environment"
else
	echo -e "\n1a - Backing up current application"
	tar  --exclude="artifacts.tar.gz" --exclude=".backup" -zcpvf app.backup.tar.gz ./* > /dev/null  2>&1
	mv app.backup.tar.gz .backup/
fi

echo -e "\n1b - Unzipping application and creating file list"
if [ "${ENV}" = "dev" ]; then
	echo -e "\n\t > Skipping unzipping application since we're in dev environment. Showing mock cleanup"
	find . \( -wholename './artifacts.tar.gz' -o -path ./storage/app -o -path ./.backup -o -path ./.git -o -path ./storage \) -prune -o -print
else
	find .  \( -wholename './artifacts.tar.gz' -o -path ./storage/app -o -path ./.backup -o -path ./.git -o -path ./storage \) -prune -o -exec rm -rf {} > /dev/null 2>&1 \;
	tar -tzpf artifacts.tar.gz > artifacts.list
	tar -zxpf artifacts.tar.gz ./
	rm artifacts.tar.gz
fi

echo -e "\n1b - Updating environment: ${ENV}"
cp ".env.$ENV" .env
cp storage/oauth-private.${ENV}.key storage/oauth-private.key
cp storage/oauth-public.${ENV}.key storage/oauth-public.key

# Going into maintenance mode
echo -e "\n2 - Going into maintenance mode"
php artisan down

# Applying migrations and optimizing application
echo -e "\n3 - Discovering packages, applying migrations and optimizing application"
php artisan package:discover
# Doing this here as there are soem issues when deploying to build environment
php artisan l5-swagger:generate
php artisan migrate --force
php artisan telescope:publish
php artisan horizon:install
php artisan horizon:publish
# php artisan prequel:update
php artisan clear
php artisan view:clear
php artisan view:cache
php artisan clear-compiled
php artisan optimize
php artisan storage:link

echo -e "\n4 - Exiting maintenance mode"
# Seems there's a laravel bug that requires an extra config clear
php artisan up

echo -e "\n5 - Ensuring crontab for laravel commands"
bash ./scripts/ensure_scheduled_commands.sh

echo -e "\nDeploy Done!"
exit 0
