#!/bin/bash

set -x
set -e

source /etc/profile.d/sh.local

if [ "$EB_ROLE" == "api" ] || [ "$EB_ROLE" == "service" ]; then
    # This file will make sure that will set the max processes and spare processes
    # according to the details provided by this machine instance.

    DEFAULT_PROCESS_MEMORY="32"
    MAX_REQUESTS="500"

    PROCESS_MAX_MB=$DEFAULT_PROCESS_MEMORY

    VCPU_CORES=$(($(lscpu | awk '/^CPU\(s\)/{ print $2 }')))

    TOTAL_MEMORY_IN_KB=$(free | awk '/^Mem:/{print $2}')
    USED_MEMORY_IN_KB=$(free | awk '/^Mem:/{print $3}')
    FREE_MEMORY_IN_KB=$(free | awk '/^Mem:/{print $4}')

    TOTAL_MEMORY_IN_MB=$((TOTAL_MEMORY_IN_KB / 1024))
    USED_MEMORY_IN_MB=$((USED_MEMORY_IN_KB / 1024))
    FREE_MEMORY_IN_MB=$((FREE_MEMORY_IN_KB / 1024))

    MAX_CHILDREN=$(( FREE_MEMORY_IN_MB / PROCESS_MAX_MB ))
    MAX_CHILDREN=${MAX_CHILDREN:-10}

    # Optimal would be to have at least 1/4th of the children filled with children waiting to serve requests.
    START_SERVERS=$((MAX_CHILDREN / 4))
    START_SERVERS=${START_SERVERS:-1}
    MIN_SPARE_SERVERS=$((MAX_CHILDREN / 4))
    MIN_SPARE_SERVERS=${MIN_SPARE_SERVES-1}

    # Optimal would be to have at most 3/4ths of the children filled with children waiting to serve requests.
    MAX_SPARE_SERVERS=$((((3 * MAX_CHILDREN) / 4)))
    MAX_SPARE_SERVERS=${MAX_SPARE_SERVERS:-1}

    sudo sed -i "s|^pm \=.*|pm = static|g" /etc/php-fpm.d/www.conf
    sudo sed -i "s|pm.max_children.*|pm.max_children = $MAX_CHILDREN|g" /etc/php-fpm.d/www.conf
    sudo sed -i "s|pm.start_servers.*|pm.start_servers = $START_SERVERS|g" /etc/php-fpm.d/www.conf
    sudo sed -i "s|pm.min_spare_servers.*|pm.min_spare_servers = $MIN_SPARE_SERVERS|g" /etc/php-fpm.d/www.conf
    sudo sed -i "s|pm.max_spare_servers.*|pm.max_spare_servers = $MAX_SPARE_SERVERS|g" /etc/php-fpm.d/www.conf

    printf "\npm.max_requests = $MAX_REQUESTS" | sudo tee -a /etc/php-fpm.d/www.conf

    # Restarting the services afterwards.
    sudo systemctl restart php-fpm.service
    sudo systemctl restart nginx.service
fi
exit 0
