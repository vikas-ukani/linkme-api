#!/bin/bash

pwd

# Get the script arguments
options=$@

# An array with all the arguments
arguments=($options)

# Loop index
index=0

for argument in $options
	do
	# Incrementing index
	index=`expr $index + 1`

	# The conditions
	case $argument in
		--env) ENV=${arguments[index]};;
		--dirGroup) dirGroup=${arguments[index]};;
		--dirOwner) dirOwner=${arguments[index]};;
	esac
done

if [ -z ${ENV} ]; then
	case ${BRANCH_NAME} in
		'master')
			ENV="production";;
		'staging')
			ENV="staging";;
		'beta')
			ENV="beta";;
		*)
			ENV="dev";;
	esac
fi

echo -e "\n1 - Deploying to environment: ${ENV}"
mkdir -p .backup
if [ $ENV != 'dev' ] && [ ! -f .env ]; then
    echo -e "APP_ENV=$ENV" > .env
fi

if [ ${ENV} = 'dev' ]; then
	echo -e "\n2a - Skipping creating backup since we're in dev environment"
else
	echo -e "\n1a - Backing up current application"
	tar  --exclude="artifacts.tar.gz" --exclude=".backup" -zcpvf app.backup.tar.gz ./*
	mv app.backup.tar.gz .backup/
fi

echo -e "\n1b - Unzipping application and creating file list"
if [ ${ENV} = 'dev' ]; then
	echo -e "\n\t > Skipping unzipping application since we're in dev environment. Showing mock cleanup"
	find .  \( -wholename './artifacts.tar.gz' -o -path ./storage/app -o -path ./.backup -o -path ./.git -o -path ./storage \) -prune -o -print
else
	echo -e "\n\t > Removing old app and extracting new one"
	find .  \( -wholename './artifacts.tar.gz' -o -path ./storage/app -o -path ./.backup -o -path ./.git -o -path ./storage \) -prune -o -exec rm -rf {} \;
	tar -tzpf artifacts.tar.gz > artifacts.list
	tar -zxpf artifacts.tar.gz ./
	rm artifacts.tar.gz
fi

echo -e "\n1c - Creating storage directories"
mkdir -p storage/cms/{combiner,twig,cache}
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/{app,temp,logs}

# Going into maintenance mode
echo -e "\n2 - Going into maintenance mode"
php artisan down

# Applying migrations and optimizing application
echo -e "\n3 - Applying migrations and optimizing application"
php artisan migrate --force
php artisan telescope:publish
php artisan storage:link
php artisan clear
php artisan view:clear
php artisan view:cache
php artisan clear-compiled
php artisan optimize

# Lets build the app
if [ -f 'scripts/build_js_app.sh' ]; then
	echo -e "\n3a - Building javascript application"
	bash scripts/build_js_app.sh {$ENV}
fi

# Now lets fix permissions
echo -e "\n4 - Fixing permissions";
#bash scripts/fix_permissions.sh ${dirOwner} ${dirGroup} > /dev/null

echo -e "\n5 - Exiting maintenance mode"
php artisan up

echo -e "\nDeploy Done!"
exit
