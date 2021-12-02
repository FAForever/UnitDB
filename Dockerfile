FROM richarvey/nginx-php-fpm:1.10.3

LABEL maintainer="rackover@racknet.noip.me"

COPY . /var/www/html
