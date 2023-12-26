#!/bin/bash
echo "Running post deploy script"

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
			ENV="production";;
	esac
fi

echo -e "\n1 - Deploying to environment: ${ENV}"
mkdir -p .backup
export -p > .env
sed -i 's/declare -x //' .env
sed -i 's/export //' .env
# sed -i 's/redis/sync/' .env
# perl -p -e 's/\$\{(\w+)\}/(exists $ENV{$1}?$ENV{$1}:"")/eg' < ./variables.sh > .env
cp .env .env.$ENV

if [ -f "./storage/oauth-private.${ENV}.key" ]; then
cp storage/oauth-private.${ENV}.key storage/oauth-private.key
cp storage/oauth-public.${ENV}.key storage/oauth-public.key
fi

# Going into maintenance mode
echo -e "\n2 - Going into maintenance mode"
php artisan down

# Applying migrations and optimizing application
echo -e "\n3 - Discovering packages, applying migrations and optimizing application"
php artisan package:discover
# Doing this here as there are soem issues when deploying to build environment
# php artisan l5-swagger:generate
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

# Now lets fix permissions
# echo -e "\n4 - Fixing permissions";
# bash scripts/fix_permissions.sh ${dirOwner} ${dirGroup} > /dev/null

echo -e "\n4 - Exiting maintenance mode"
php artisan up

echo -e "\n5 - Ensuring crontab for laravel commands"
bash scripts/cloud/eb/ensure_scheduled_commands.sh

echo -e "\nDeploy Done!"
exit 0
