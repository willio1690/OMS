ARG  HTTPD_VERSION=0.1

FROM shopexhub/httpd2:oms-8.2.29-apache-alpine3.22-v1.0

ENV APP_DIR=/data/httpd/oms
RUN set -eux; \
	mkdir -p "$APP_DIR"; \
	chown -R apache:apache "$APP_DIR";

WORKDIR $APP_DIR

COPY . $APP_DIR

# 给目录权限
RUN chown -R apache:apache ./config \
	&& chown -R apache:apache ./data \
	&& chown -R apache:apache ./public \
	&& ln -sf /dev/stderr  /var/log/apache2/error.log

COPY ./docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY ./app/base/examples/config.php $APP_DIR/config/config.php
COPY ./app/taskmgr/config/config.sample.php $APP_DIR/app/taskmgr/config/config.php


RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# 安装composer
RUN mv ./tools/composer.phar /usr/local/bin/composer; \
	composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/; \
	composer selfupdate; \
	composer install --no-dev;

RUN rm -rf ./docker/docker-entrypoint.sh ./docker/Dockerfile .git .gitlab-ci.yml .gitignore /var/www/html/phpinfo.php ./composer.json ./composer.lock ./tools/composer.phar

# 配置文件
ENTRYPOINT ["docker-entrypoint.sh", "apache2-foreground"]
#CMD ["apache2-foreground"]
