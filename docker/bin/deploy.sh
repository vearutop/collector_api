#!/bin/bash

set -e

SRC_PATH="~/git/collector_api"
SRV_NAME="128.199.115.193"

echo "Deploy HackerBoard API on $SRV_NAME"
ssh root@$SRV_NAME "cd $SRC_PATH; \
    git pull; \
    docker build -t collector_api .; \
    docker stop collector_api; docker rm collector_api; \
    docker run -d --name collector_api -P -p 8889:80 \
        collector_api;"

echo "== Deploy  HackerBoard API DONE. Tailling the log, you can safely stop with CTRL+C"
ssh root@$SRV_NAME "docker logs -f collector_api;";