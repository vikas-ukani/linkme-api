#!/bin/bash
source <(sed -E -n 's/[^#]+/export &/ p' /opt/elasticbeanstalk/deployment/custom_env_var)
bash scripts/eb_post_deploy.sh --env $APP_ENV $APP_ENV
exit 0
