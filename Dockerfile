#FROM php:8.1-fpm-alpine

FROM wyveo/nginx-php-fpm:php81


# RUN apk add zlib-dev libpng-dev
#RUN docker-php-ext-install mysqli pdo pdo_mysql gd


#RUN apk add nginx

WORKDIR /code

COPY . .

COPY nginx.conf /etc/nginx/nginx.conf

# RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/nginx/ssl/www.598623u-no-on.fr.key -out /etc/nginx/ssl/www.598623u-no-on.fr.crt \
# -subj "/C=US/ST=New Sweden/L=Stockholm/O=NGINX/OU=NGINX/CN=www.598623u-no-on.fr/emailAddress=nicolas.ourdouille@outlook.fr"


ENV DOCKER_ENV true
ENV APP_ENV=prod
RUN rm -rf .env.local