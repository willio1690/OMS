ARG  HTTPD_VERSION=0.1

FROM shopexhub/httpd2:supervisor-php82-alpine3.22

ENV APP_DIR=/data/httpd/oms

WORKDIR $APP_DIR
COPY . $APP_DIR

RUN mkdir /etc/supervisor.d\
    && cp -f /data/httpd/oms/docker/supervisor/conf/* /etc/supervisor.d/

COPY ./docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN rm -rf ./docker .git .gitlab-ci.yml .gitignore /var/www/html/phpinfo.php

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# 安装composer
RUN mv ./tools/composer.phar /usr/local/bin/composer; \
	composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/; \
	composer selfupdate; \
	composer install --no-dev;

# 配置文件
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh", "supervisord", "-c", "/etc/supervisord.conf", "-n"]