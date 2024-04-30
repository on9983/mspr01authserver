#FROM php:8.1-fpm-alpine

FROM wyveo/nginx-php-fpm:php81


# RUN apk add zlib-dev libpng-dev
#RUN docker-php-ext-install mysqli pdo pdo_mysql gd


#RUN apk add nginx

WORKDIR /code

COPY . .

COPY nginx.conf /etc/nginx/nginx.conf
#COPY php-docker.conf /etc/php81/php-fpm.d/www.conf

ENV DOCKER_ENV true

