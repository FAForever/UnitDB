FROM richarvey/nginx-php-fpm:1.8.2

LABEL maintainer="rackover@racknet.noip.me"

COPY . /var/www/html
