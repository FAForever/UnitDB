FROM richarvey/nginx-php-fpm:3.1.6

LABEL maintainer="rackover@racknet.noip.me"

RUN apk update
RUN apk add git

COPY ./www/ /var/www/html
