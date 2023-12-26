#!/bin/bash

set -e
set -x

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
	esac
done

if [ -z ${ENV} ]; then
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

bash ./scripts/vm/setup.sh ${ENV}

echo "Deploying to {$ENV}"
pwd
if [ "$ENV" = 'production' ]; then
    BUILD_URL="$HOST_PRODUCTION"
    APP_FOLDER="$DIR_PRODUCTION"
	USER="$USER_PRODUCTION"
else
    BUILD_URL="$HOST_STAGING"
    APP_FOLDER="$DIR_STAGING"
	USER="$USER_STAGING"
fi

echo "Creating env File"
# export -p > .env
# sed -i 's/declare -x //' .env
# sed -i 's/export //' .env
# perl -p -e 's/\$\{(\w+)\}/(exists $ENV{$1}?$ENV{$1}:"")/eg' < ./variables.sh > .env
# Replace problematic environment variables
sed -i "s/QUEUE_DRIVER=(.*)/QUEUE_DRIVER=$QUEUE_DRIVER/" .env
sed -i "s/CACHE_DRIVER=(.*)/CACHE_DRIVER=$CACHE_DRIVER/" .env
sed -i "s/QUEUE_CONNECTION=(.*)/QUEUE_CONNECTION=$QUEUE_CONNECTION/" .env
sed -i "s/BROADCAST_DRIVER=(.*)/BROADCAST_DRIVER=$BROADCAST_DRIVER/" .env
cp .env .env.$ENV

echo "Copying app to remote server: ${BUILD_URL}"
SSH_OPTS='-o PasswordAuthentication=no -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ChallengeResponseAuthentication=no'
scp ${SSH_OPTS} artifacts.tar.gz ${USER}@${BUILD_URL}:~${USER}/${APP_FOLDER}
ssh ${SSH_OPTS} ${USER}@${BUILD_URL} "mkdir -p ~${USER}/${APP_FOLDER}/scripts/vm" && scp ${SSH_OPTS} ./scripts/vm/post_deploy.sh ${USER}@${BUILD_URL}:~${USER}/${APP_FOLDER}/scripts/vm

echo "Logging into ${BUILD_URL} and updating app"
ssh ${SSH_OPTS} ${USER}@${BUILD_URL} ENV=$ENV APP_FOLDER=$APP_FOLDER USER=${USER} 'bash -sex' << 'ENDSSH'
pwd
cd ~/${APP_FOLDER};
bash scripts/vm/post_deploy.sh --env=${ENV}
ENDSSH

echo "App successfully deployed to ${BUILD_URL}"
