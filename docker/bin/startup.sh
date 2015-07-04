#!/bin/bash

set -e
service sshd start
service php-fpm start
service memcached start

echo "Start Nginx"
/usr/sbin/nginx -c /etc/nginx/nginx.conf