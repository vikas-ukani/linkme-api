#!/bin/sh

# Install PhpRedis

# Laravel uses by default PhpRedis, so the extension needs to be installed.
# https://github.com/phpredis/phpredis

# For Predis, this extension is not necessarily needed.
# Enabled by default since it's latest Laravel version's default driver.

sudo yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm
sudo yum install -y --enablerepo=remi-php80 php-redis
exit 0
