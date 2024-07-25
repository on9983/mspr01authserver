FROM wyveo/nginx-php-fpm:php81

WORKDIR /code

COPY . .

COPY nginx.conf /etc/nginx/nginx.conf

ENV DOCKER_ENV=true
ENV APP_ENV=prod
