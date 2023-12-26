# syntax=docker/dockerfile:experimental
FROM debian:stretch
ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && \
    apt-get install -y -qq --no-install-recommends sudo wget gnupg2 apt-transport-https lsb-release ca-certificates curl && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    echo "deb [signed-by=/usr/share/keyrings/cloud.google.gpg] https://packages.cloud.google.com/apt cloud-sdk main" | tee -a /etc/apt/sources.list.d/google-cloud-sdk.list && \
    curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | apt-key --keyring /usr/share/keyrings/cloud.google.gpg add - && \
    curl -sL https://deb.nodesource.com/setup_14.x | bash - && \
    apt-get update && apt-get install -y -qq --no-install-recommends git openssh-client \
    apt-transport-https lsb-release ca-certificates groff jq \
    build-essential nodejs software-properties-common \
    php7.4-intl php7.4-gd php7.4-fpm git redis-server \
    php7.4-cli php7.4-curl php7.4-pgsql php7.4-ldap \
    php7.4-sqlite php7.4-mysql php7.4-zip php7.4-xml \
    php7.4-mbstring php7.4-dev make libmagickcore-6.q16-2-extra unzip \
    php7.4-redis php7.4-imagick php7.4-dev php7.4-bcmath \
    php7.4-mongodb php7.4-json nginx supervisor net-tools \
    libsystemd-dev python python-pip python-dev python2.7 python2.7-dev \
    python3 python3-pip python3-dev libpng-dev nano cron \
    python-setuptools python3-setuptools google-cloud-sdk nodejs && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN cd /tmp/ && wget https://github.com/nikic/php-ast/archive/master.zip && unzip master.zip
RUN cd /tmp/php-ast-master/ && phpize && ./configure && make && make install &&rm -rf /tmp/php-ast-master/
RUN echo "extension=ast.so" >> /etc/php/7.4/cli/conf.d/20-ast.ini

RUN cd /tmp && wget -O php-systemd-src.zip https://github.com/systemd/php-systemd/archive/master.zip && \
    unzip php-systemd-src.zip && cd /tmp/php-systemd-master && phpize && \
    ./configure --with-systemd && make && make install && rm -rf /tmp/php-systemd-master && \
    echo "extension=systemd.so" >> /etc/php/7.4/mods-available/systemd.ini

RUN phpenmod zip intl gd systemd
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');"

RUN phpdismod xdebug
RUN npm i -g npm
RUN pip install --upgrade pip
ENV PATH="~/.local/bin:${PATH}"
RUN echo $PATH
RUN ls -hl
# Configure nginx
# Remove default server definition
# RUN if [ -f '/etc/nginx/nginx.conf' ]; then rm /etc/nginx/nginx.conf; fi
# COPY .docker/config/nginx.conf /etc/nginx/
RUN if [ -f '/etc/nginx/sites-enabled/default' ]; then rm /etc/nginx/sites-enabled/default; fi
COPY .docker/config/nginx-default.conf /etc/nginx/sites-enabled/default

# Configure PHP-FPM
COPY .docker/config/opcache.ini /etc/php/7.4/cli/conf.d/opcache.ini
COPY .docker/config/opcache.ini /etc/php/7.4/fpm/conf.d/opcache.ini
RUN mkdir -p /run/php
# COPY .docker/config/fpm-pool.conf /etc/php/7.4/fpm/pool.d/www.conf

# Copy custom redis config
RUN if [ -f '/etc/redis/redis.conf' ]; then rm /etc/redis/redis.conf; fi
COPY .docker/config/redis.conf /etc/redis/redis.conf

# Configure supervisord
COPY .docker/config/supervisor-services.conf /etc/supervisor/conf.d/supervisor-services.conf
COPY .docker/config/supervisord.conf /etc/supervisor/supervisord.conf

# Make sure entry point files are copies over
COPY scripts/entrypoint.sh /etc/entrypoint.sh
RUN chmod +x /etc/entrypoint.sh
RUN ls -l /etc/entrypoint.sh

# Setup document root
RUN mkdir -p /var/www/html

# Make sure files/folders needed by the processes are accessable when they run under the www-data user
RUN chown -R www-data:www-data /var/www && \
    chown -R www-data:www-data /run && \
    chown -R www-data:www-data /var/lib/nginx && \
    chown -R www-data:www-data /var/log/nginx

# Switch to use a non-root user from here on
USER www-data

# Add application
WORKDIR /var/www/html
RUN --mount=type=secret,id=linkme-backend,dst=.env
COPY --chown=www-data ./ .
RUN chmod +x scripts/*
RUN scripts/build.sh --env production production
RUN scripts/post_deploy.sh --env production production

# witch back to the root user
USER root

# Expose the port nginx is reachable on
EXPOSE 80
EXPOSE 443
EXPOSE 5000
EXPOSE 9000

# Enable recently installed packages to run
# RUN echo "exit 0" > /usr/sbin/policy-rc.d
ENTRYPOINT ["/etc/entrypoint.sh"]

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:5000/fpm-ping
