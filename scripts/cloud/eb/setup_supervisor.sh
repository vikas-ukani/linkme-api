#!/bin/bash

set -e
set -x

#
# Author: Günter Grodotzki (gunter@grodotzki.co.za)
# Version: 2015-04-25
#
# install supervisord
#
# See:
# - https://github.com/Supervisor/initscripts
# - http://supervisord.org/

if [[ "${SUPERVISE}" -eq "enable" ]]
then

  export HOME="/root"
  export PATH="/sbin:/bin:/usr/sbin:/usr/bin:/opt/aws/bin"

easy_install supervisor

  cat <<'EOB' > /etc/supervisord.conf
; supervisor config file

[unix_http_server]
file=/var/run/supervisor.sock   ; (the path to the socket file)
chmod=0700                       ; sockef file mode (default 0700)

[supervisord]
logfile=/var/log/supervisor/supervisord.log ; (main log file;default $CWD/supervisord.log)
pidfile=/var/run/supervisord.pid ; (supervisord pidfile;default supervisord.pid)
childlogdir=/var/log/supervisor            ; ('AUTO' child log dir, default $TEMP)

; the below section must remain in the config file for RPC
; (supervisorctl/web interface) to work, additional interfaces may be
; added by defining them in separate rpcinterface: sections
[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[supervisorctl]
serverurl=unix:///var/run/supervisor.sock ; use a unix:// URL  for a unix socket

; The [include] section can just contain the "files" setting.  This
; setting can list multiple files (separated by whitespace or
; newlines).  It can also contain wildcards.  The filenames are
; interpreted as relative to this file.  Included files *cannot*
; include files themselves.

[include]
files = /etc/supervisor/conf.d/*.conf
; Change according to your configurations
EOB

mkdir -p /etc/supervisor/conf.d/
mkdir -p /var/www/html/storage/logs/
touch /etc/supervisor/conf.d/laravel-worker.conf
CORES=$((`nproc` / 2))
CORES_FOR_DEFAULT=$((($CORES * 2) - 1))

if [[ "$EB_ROLE" -eq 'worker' || "$EB_ROLE" -eq 'combined' ]]; then
    # Setup the high priority queue
    QUEUE_ARGS="--sleep=2 --tries=3 --timeout=120 --queue=default --memory=256"

    cat <<EOB > /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work $QUEUE_ARGS
autostart=true
autorestart=true
user=root
numprocs=$CORES_FOR_DEFAULT
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
EOB

    # Setup the high priority queue
    QUEUE_ARGS="--sleep=2 --tries=3 --timeout=1810 --queue=high --memory=256"
    cat <<EOB > /etc/supervisor/conf.d/laravel-worker-high.conf
[program:laravel-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work $QUEUE_ARGS
autostart=true
autorestart=true
user=root
numprocs=$CORES
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
EOB
fi

if [[ "$EB_ROLE" -eq 'api' || "$EB_ROLE" -eq 'combined' ]]; then
    QUEUE_ARGS="--tries=3 --timeout=89 --queue=default"
    cat <<EOB > /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work $QUEUE_ARGS
autostart=true
autorestart=true
user=root
numprocs=$CORES_FOR_DEFAULT
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
EOB
fi

touch /etc/supervisor/conf.d/laravel-horizon.conf
cat <<'EOB' > /etc/supervisor/conf.d/laravel-horizon.conf
[program:laravel-horizon]
process_name=%(program_name)s
command=php /var/www/html/artisan horizon
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/horizon.log
EOB

touch /etc/supervisor/conf.d/laravel-telescope-prune.conf
cat <<'EOB' > /etc/supervisor/conf.d/laravel-telescope-prune.conf
[program:laravel-telescope-prune]
process_name=%(program_name)s
command=php /var/www/html/artisan telescope:prune --hours=168
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/horizon.log
EOB

  mkdir -p /var/log/supervisor
  touch /var/log/supervisor/supervisord.log
  if [[ ! -f '/var/run/supervisord.pid' ]]
  then
    /usr/local/bin/supervisord -c /etc/supervisord.conf
  fi
  /usr/local/bin/supervisorctl reread
  /usr/local/bin/supervisorctl update
  /usr/local/bin/supervisorctl start laravel-worker:*
  /usr/local/bin/supervisorctl start laravel-horizon:*
  /usr/local/bin/supervisorctl status
fi
