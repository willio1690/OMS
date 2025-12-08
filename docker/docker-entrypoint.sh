#!/bin/sh

# default timezone
if [ ! -n "$TZ" ]; then
    export TZ="Asia/Shanghai"
fi

# set timezone
ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && \
echo $TZ > /etc/timezone


# HOST绑定
if [[ ! -z "$CLOUD_HOSTBIND_NAME" ]]; then
	echo "$CLOUD_HOSTBIND_NAME" >> /etc/hosts
fi

if [ ! -z "$1" ]; then
    exec "$@"
fi
