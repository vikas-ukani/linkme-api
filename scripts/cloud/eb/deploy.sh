#!/bin/bash

set -e

set -x

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
		'beta')
			ENV="beta";;
		*)
			ENV="production";;
	esac
fi

echo "Deploying to {$ENV}"

if [ "${ENV}" = 'production' ]; then
    BUILD_URL="$HOST_PRODUCTION"
	APPLICATION_NAME="$APPLICATION_NAME_PRODUCTION"
	ENVIRONMENT_NAME="$ENVIRONMENT_NAME_PRODUCTION"
	WORKER_ENVIRONMENT_NAME="$WORKER_ENVIRONMENT_NAME_PRODUCTION"
else
    BUILD_URL="$HOST_STAGING"
	APPLICATION_NAME="$APPLICATION_NAME_STAGING"
	ENVIRONMENT_NAME="$ENVIRONMENT_NAME_STAGING"
	WORKER_ENVIRONMENT_NAME="$WORKER_ENVIRONMENT_NAME_STAGING"
fi

if [ -z $WORKER_ENVIRONMENT_NAME ]; then
mkdir -p .elasticbeanstalk
cp ./elasticbeanstalk-config.tmpl.yml .elasticbeanstalk/config.yml
sed -i "s~%%APPLICATION_NAME%%~${APPLICATION_NAME}~g" .elasticbeanstalk/config.yml
# Deploy worker first
sed -i "s~%%ENVIRONMENT_NAME%%~${ENVIRONMENT_NAME}~g" .elasticbeanstalk/config.yml
sed -i "s~%%AWS_DEFAULT_REGION%%~${AWS_DEFAULT_REGION}~g" .elasticbeanstalk/config.yml

# Need to do this here because gitgnore prevents deployment of the current environment file
cat .elasticbeanstalk/config.yml
eb use "${ENVIRONMENT_NAME}" -v
eb deploy "${ENVIRONMENT_NAME}" -v
echo "App API successfully deployed to ${BUILD_URL}"
else
mkdir -p .elasticbeanstalk
cp ./elasticbeanstalk-config.tmpl.yml .elasticbeanstalk/config.yml
sed -i "s~%%APPLICATION_NAME%%~${APPLICATION_NAME}~g" .elasticbeanstalk/config.yml
# Deploy worker first
sed -i "s~%%ENVIRONMENT_NAME%%~${WORKER_ENVIRONMENT_NAME}~g" .elasticbeanstalk/config.yml
sed -i "s~%%AWS_DEFAULT_REGION%%~${AWS_DEFAULT_REGION}~g" .elasticbeanstalk/config.yml

# Need to do this here because gitgnore prevents deployment of the current environment file
cat .elasticbeanstalk/config.yml
eb use "${WORKER_ENVIRONMENT_NAME}" -v
eb deploy "${WORKER_ENVIRONMENT_NAME}"
echo "App worker successfully deployed"

sed -i "s~${WORKER_ENVIRONMENT_NAME}~${ENVIRONMENT_NAME}~g" .elasticbeanstalk/config.yml
# Need to do this here because gitgnore prevents deployment of the current environment file
cat .elasticbeanstalk/config.yml
eb use "${ENVIRONMENT_NAME}" -v
eb deploy "${ENVIRONMENT_NAME}"
echo "App API successfully deployed to ${BUILD_URL}"
fi

exit 0
