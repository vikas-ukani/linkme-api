#!/bin/bash

# service redis-server start
# service php7.3-fpm start
# service nginx start
service cron start
service supervisor start

supervisorctl reread
supervisorctl update
supervisorctl start *
supervisorctl status
# https://stackoverflow.com/questions/24241292/dockerized-nginx-is-not-starting
while true; do sleep 1d; done
