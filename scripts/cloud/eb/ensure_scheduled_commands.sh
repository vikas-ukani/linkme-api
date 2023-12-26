#!/bin/bash
set -u
set -x

dir=$(pwd)
cronUser=$(stat -c '%U' "$dir")
currentUser=$(whoami)
appDir="/var/app/current"
cronUserHome="/home/$cronUser"
supervisorctlBin='/usr/bin/supervisorctl'
if [ -f /usr/local/bin/supervisorctl ]; then
    supervisorctlBin='/usr/local/bin/supervisorctl'
fi
declare -a taskCommands=(
    "\* \* \* \* \* cd $appDir && /usr/bin/php artisan schedule:run >> /dev/null 2>&1"
    "\*/30 \* \* \* \* cd $appDir && /usr/bin/php artisan queue:restart >> /dev/null 2>&1"
    "0 0 \* \* SUN php /var/www/html/artisan telescope:prune --hours=168")

if [ "$currentUser" = "root" ]; then
	cronContents="$(crontab -u "$cronUser" -l 2>&1)"
    # Make sure the current user has a home directory
    if [ ! -d ${cronUserHome} ]; then
        mkdir -p ${cronUserHome}
        chown -cR ${cronUser}:${cronUser} ${cronUserHome}
    fi
else
	cronContents="$(crontab -l 2>&1)"
fi

for taskCommand in "${taskCommands[@]}"
do
    # If the cron entry doesn't exist then create it
    if ! echo "$cronContents" | grep -q "$taskCommand"; then
        taskCommand=$(echo $taskCommand | sed 's/\\\*/*/g')
        echo -e "\tTask doesn't exist. Adding: $taskCommand"
        if [ "$currentUser" = "root" ]; then
            (crontab -u "$cronUser" -l 2>/dev/null; echo -e "$taskCommand";) | crontab -u "$cronUser" -
        else
            (crontab -l 2>/dev/null; echo -e "$taskCommand";) | crontab -
        fi
    else
        echo -e "\tTask exists. Skipping"
    fi
done
