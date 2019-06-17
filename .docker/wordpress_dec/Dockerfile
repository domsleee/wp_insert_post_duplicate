FROM wordpress:7.3-fpm-sysv

RUN set -e; \
  docker-php-ext-install pcntl;

RUN set -e; \
  curl -L https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar > /usr/local/bin/wp; \
  chmod +x /usr/local/bin/wp;

RUN set -e; \
  apt-get update; \
  apt-get install -y less mysql-client;
