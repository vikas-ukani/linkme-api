#!/bin/sh

# Restart services
if [[ "$EB_ROLE" -eq 'worker'  ||  "$EB_ROLE" -eq 'combined' ]]; then
    supervisorctl restart all
fi
exit 0
