FROM richarvey/nginx-php-fpm:1.10.4

LABEL maintainer="rackover@racknet.noip.me"

COPY . /var/www/html
