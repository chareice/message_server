#!/bin/sh
echo "start migrate"
bash -c 'cd /var/www/webapp; php artisan migrate';
echo "migrate finshed"

echo 'start apache..'
apache2-foreground
