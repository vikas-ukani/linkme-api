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

if [[ "$SUPERVISE" -eq "enable" ]]
then

  export HOME="/root"
  export PATH="/sbin:/bin:/usr/sbin:/usr/bin:/opt/aws/bin"

if [ ! -f /etc/supervisord.conf ]; then
  cat <<'EOB' > /etc/supervisord.conf
; supervisor config file

[unix_http_server]
file=/var/run/supervisor/supervisor.sock   ; (the path to the socket file)
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
serverurl=unix:///var/run/supervisor/supervisor.sock ; use a unix:// URL  for a unix socket

; The [include] section can just contain the "files" setting.  This
; setting can list multiple files (separated by whitespace or
; newlines).  It can also contain wildcards.  The filenames are
; interpreted as relative to this file.  Included files *cannot*
; include files themselves.

[include]
files = /etc/supervisord.d/*.ini
; Change according to your configurations
EOB
fi

mkdir -p /etc/supervisord.d/
CORES=$(nproc)
CORES_FOR_DEFAULT=$((CORES*2))

# Run queues only on the worker server
if [[ "$EB_ROLE" -eq 'worker'  ||  "$EB_ROLE" -eq 'combined' ]]; then
    touch /etc/supervisord.d/laravel-worker.ini
    touch /etc/supervisord.d/laravel-worker-high.ini
    # Setup the high priority queue
    QUEUE_ARGS="--sleep=10 --tries=5 --timeout=120 --queue=default --memory=64 --stop-when-empty"

    cat <<EOB > /etc/supervisord.d/laravel-worker.ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/app/current/artisan queue:work $QUEUE_ARGS
autostart=true
autorestart=true
user=root
numprocs=$CORES_FOR_DEFAULT
startsecs=0
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
EOB

    # Setup the high priority queue
    QUEUE_ARGS="--sleep=30 --tries=2 --timeout=1810 --queue=high --memory=128 --stop-when-empty"
    cat <<EOB > /etc/supervisord.d/laravel-worker-high.ini
[program:laravel-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/app/current/artisan queue:work $QUEUE_ARGS
autostart=true
autorestart=true
user=root
numprocs=$CORES
startsecs=0
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
EOB
fi

if [[ "$EB_ROLE" -eq 'api'  ||  "$EB_ROLE" -eq 'combined' ]]; then
# Run horizon only on the api servers
    touch /etc/supervisord.d/laravel-horizon.ini
    cat <<'EOB' > /etc/supervisord.d/laravel-horizon.ini
[program:laravel-horizon]
process_name=%(program_name)s
command=php /var/app/current/artisan horizon
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/var/log/laravel-horizon.log
EOB
fi

  mkdir -p /var/log/supervisor
  mkdir -p /var/run/supervisor
  touch /var/log/supervisor/supervisord.log
  if [[ ! -f '/var/run/supervisord.pid' ]]
  then
    supervisord -c /etc/supervisord.conf
  fi
  supervisorctl reread
  supervisorctl update
  if [[ "$EB_ROLE" -eq 'worker'  ||  "$EB_ROLE" -eq 'combined' ]]
  then
    supervisorctl start laravel-worker:*
    supervisorctl restart laravel-worker-high:*
  else
    supervisorctl start laravel-horizon:*
  fi
  supervisorctl status
fi
exit 0
