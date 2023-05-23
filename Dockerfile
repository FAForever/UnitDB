FROM richarvey/nginx-php-fpm:3.1.4

LABEL maintainer="rackover@racknet.noip.me"

COPY . /var/www/html