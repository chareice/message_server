#!/bin/bash
#docker run --name message_server_mysql -e MYSQL_ROOT_PASSWORD=pass -d mysql --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
#docker run -it --link message_server_mysql:mysql --rm mysql sh -c 'exec mysql -h"$MYSQL_PORT_3306_TCP_ADDR" -P"$MYSQL_PORT_3306_TCP_PORT" -uroot -p"$MYSQL_ENV_MYSQL_ROOT_PASSWORD"'
. config-default.sh

docker stop $containerName && docker rm $containerName

docker run --name $containerName -e LANG=C.UTF-8 \
          --link message_server_mysql:mysql\
          -d -p 8011:80 $imageName
