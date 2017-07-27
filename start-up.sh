#!/bin/bash
cd /var/www/webapp;
echo "start migrate"
php artisan migrate --force
echo "migrate finshed"

echo 'start apache..'
a2enmod rewrite
apache2-foreground
