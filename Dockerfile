FROM wyveo/nginx-php-fpm:php81

WORKDIR /code

COPY . .

COPY nginx.conf /etc/nginx/nginx.conf

COPY .env.dockerprod ./.env

ENV DOCKER_ENV=true
ENV APP_ENV=prod
