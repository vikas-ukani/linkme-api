#!/bin/bash
pwd
if [ -f 'package.json' ]; then
    ORIGIN_DIR=$(pwd)
    APP_DIR='./'
    ENV=$1

    if [ -z "${ENV}" ]; then
        ENV='development'
    fi

    if [ "${ENV}" == 'dev' ]; then
        ENV='development'
    fi

    if [ "${ENV}" == 'development' ]; then
        BUILD_OPTS=""
    elif [ "${ENV}" == 'staging' ]; then
        # We will always build for production if we're not in dev mode
        ENV="staging"
        BUILD_OPTS=""
    elif [ "${ENV}" == 'staging' ]; then
        # We will always build for production if we're not in dev mode
        ENV="staging"
        BUILD_OPTS="--staging"
    else
        # We will always build for production if we're not in dev mode
        ENV="production"
        BUILD_OPTS="--production"
    fi

    echo -e "App: Building in environment: ${ENV}"
    echo -e "App: Changing to ${APP_DIR}"
    cd "${APP_DIR}" || return
    if [ -d node_modules ]; then
        rm -fr node_modules
    fi

    echo -e "App: Installing node modules"
    npm install --unsafe-perm
    npm install --global --unsafe-perm cross-env

    echo -e "App: Building assets"
    echo -e "App: Copying config from ${ENV}"
    # cp client/config/${ENV}.js client/config/config.js
    echo -e "App: Build command: NODE_ENV=${ENV} npm run build ${BUILD_OPTS}"
    export NODE_ENV="${ENV}"
    npm run build ${BUILD_OPTS}

    echo -e "App: Done"
    cd "${ORIGIN_DIR}" || return
    exit 0
fi
