#!/bin/bash
set -x
set -e

# bash ./setup.sh

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

echo -e "\n1 - Building environment: ${ENV}"
# export -p > .env
# sed -i 's/declare -x //' .env
# sed -i 's/declare -fx //' .env
# sed -i 's/export //' .env
perl -p -e 's/\$\{(\w+)\}/(exists $ENV{$1}?$ENV{$1}:"")/eg' < ./scripts/variables.sh > .env
#sed -i 's/redis/sync/' .env
cp .env .env.$ENV

echo -e "\n1a - Creating storage directories"
mkdir -vp storage/cms/{combiner,twig,cache}
mkdir -vp storage/framework/{cache,sessions,views}
mkdir -vp storage/{app,temp,logs}

# Now update composer with new packages
echo -e "\n2 - Installing composer packages"

echo -e "\n2a - Ensuring proper Nova Install"
mkdir -p vendor/laravel
cd vendor/laravel/
if [ -L 'nova' ]; then
    if [ -e 'nova' ]; then
        echo -e "\t- Nova link is valid"
    else
        rm nova
        ln -s -f ../../nova nova
    fi
elif [ -e 'nova' ]; then
    echo -e "\t- Nova is valid"
else
    ln -s -f ../../nova nova
fi
cd ../../
echo -e "\n2 - Continuing install of composer packages"
php composer.phar install -vvv --no-suggest --no-scripts --ignore-platform-reqs
php composer.phar dumpautoload --optimize --no-scripts

echo -e "\n3 - Zipping app"
if [ -f 'artifacts.tar.gz' ]; then
	rm artifacts.tar.gz
fi

echo "Creating env File"
printenv | sed 's/=\(.*\)/="\1"/' > .env
printenv | sed 's/=\(.*\)/="\1"/' > .env.$ENV

echo -e "\n4 - Creating artifacts"
if [ ${ENV} = 'dev' ]; then
	echo -e "\n4a - Skipping creating real artifacts since we're in dev environment"
else
	shopt -s dotglob
	tar --exclude='./storage' --exclude='./.git' --exclude="artifacts.tar.gz" --exclude=".backup" -zcpvf artifacts.tar.gz ./*  .env .env.$ENV > artifacts.create.log 2>&1
fi

# Lets build the app
if [ -f './build_js_app.sh' ]; then
	echo -e "\n3a - Building javascript application"
	bash scripts/build_js_app.sh $ENV
fi

echo -e "\nBuild Done!"
ls -al
exit 0
