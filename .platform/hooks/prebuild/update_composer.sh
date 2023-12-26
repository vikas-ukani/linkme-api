#!/bin/sh

# Update Composer binary.

export COMPOSER_HOME=/root

#sudo COMPOSER_MEMORY_LIMIT=-1 /usr/bin/composer.phar self-update
/usr/bin/composer.phar self-update
exit 0
