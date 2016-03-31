FROM php:7.0.4-apache
RUN php -r "readfile('https://getcomposer.org/installer');" > composer-setup.php \
    && php -r "if (hash('SHA384', file_get_contents('composer-setup.php')) === '41e71d86b40f28e771d4bb662b997f79625196afcca95a5abf44391188c695c6c1456e16154c75a211d238cc3bc5cb47') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer

RUN apt-get update && apt-get install -y libmcrypt-dev git zlib1g-dev
RUN docker-php-ext-install mbstring mcrypt pdo_mysql zip
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/webapp/public#' /etc/apache2/apache2.conf
COPY . /var/www/webapp
RUN cd /var/www/webapp && composer install
CMD ['/var/www/webapp/start-up.sh']
