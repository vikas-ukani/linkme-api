#!/bin/bash
set -x
set -e

bash scripts/setup.sh

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
		'test'|'testing')
			ENV="testing";;
		*)
			ENV="dev";;
	esac
fi
cp .env.testing .env

echo -e "\n1 - Creating storage directories"
mkdir -vp storage/cms/{combiner,twig,cache}
mkdir -vp storage/framework/{cache,sessions,views}
mkdir -vp storage/{app,temp,logs}

# Now update composer with new packages
echo -e "\n2 - Installing composer packages"
# php composer.phar clear-cache
# rm -fr vendor
#php composer.phar install --profile -vvv --optimize-autoloader --no-suggest --no-scripts --no-dev
php composer.phar install -vvv --no-suggest --no-scripts
php composer.phar dumpautoload --optimize --no-scripts

echo -e "\nBuild Done!"
exit 0
