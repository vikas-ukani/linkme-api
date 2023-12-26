#!/bin/bash

# Install ssh-agent if not already installed, it is required by Docker.
# (change apt-get to yum if you use a CentOS-based image)
which ssh-agent || ( apt-get update -y && apt-get install openssh-client -y )

which rsync || ( apt-get update -y && apt-get install rsync -y )

# Run ssh-agent (inside the build environment)
mkdir -vp ~/.ssh
eval "$(ssh-agent -s)"

# Add the SSH key stored in SSH_PRIVATE_KEY variable to the agent store
#chmod 0600 scripts/.id_rsa*
#ssh-add scripts/.id_rsa\

if [ "$1" = 'production' ]; then
    KEY="$SSH_PRIVATE_KEY"
else
    KEY="$SSH_PRIVATE_KEY_STAGING"
fi

if [ ! -f "$HOME/.ssh/id_rsa" ]; then
    echo "$KEY" > ~/.ssh/id_rsa
    chmod 0600 ~/.ssh/id_rsa
    ssh-add ~/.ssh/id_rsa
fi

# For Docker builds disable host key checking. Be aware that by adding that
# you are suspectible to man-in-the-middle attacks.
# WARNING: Use this only with the Docker executor, if you use it with shell
# you will overwrite your user's SSH config.
[ -f /.dockerenv ] && echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > ~/.ssh/config

touch ~/.ssh/known_hosts

{
    ssh-keyscan -H gitlab.com
    ssh-keyscan -H github.com
    ssh-keyscan -H bitbucket.org
} >> ~/.ssh/known_hosts
