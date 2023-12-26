#!/bin/bash

set -e
set -x

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

bash scripts/setup.sh ${ENV}

echo "Deploying to {$ENV}"
cd ../

if [ "$ENV" = 'production' ]; then
    BUILD_URL="$HOST_PRODUCTION"
    APP_FOLDER="$DIR_PRODUCTION"
	USER="$USER_PRODUCTION"
else
    BUILD_URL="$HOST_STAGING"
    APP_FOLDER="$DIR_STAGING"
	USER="$USER_STAGING"
fi

echo "Copying app to remote server: ${BUILD_URL}"
SSH_OPTS='-o PasswordAuthentication=no -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ChallengeResponseAuthentication=no'
scp ${SSH_OPTS} artifacts.tar.gz ${USER}@${BUILD_URL}:~${USER}/${APP_FOLDER}
ssh ${SSH_OPTS} ${USER}@${BUILD_URL} "mkdir -p ~${USER}/${APP_FOLDER}/scripts" && scp ${SSH_OPTS} scripts/cloud/gcp/post_deploy.sh ${USER}@${BUILD_URL}:~${USER}/${APP_FOLDER}/scripts/

echo "Logging into ${BUILD_URL} and updating app"
ssh ${SSH_OPTS} ${USER}@${BUILD_URL} ENV=$ENV APP_FOLDER=$APP_FOLDER USER=${USER} 'bash -sex' << 'ENDSSH'
pwd
cd ~/${APP_FOLDER};
bash scripts/cloud/gcp/post_deploy.sh --env=${ENV}
ENDSSH

echo "App successfully deployed to ${BUILD_URL}"
