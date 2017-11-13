FROM bmoorman/ubuntu

ENV HTTPD_SERVERNAME="localhost"

ARG DEBIAN_FRONTEND="noninteractive"

RUN apt-get update \
 && apt-get install --yes --no-install-recommends \
    apache2 \
    libapache2-mod-php \
    php-sqlite3 \
 && apt-get autoremove --yes --purge \
 && apt-get clean \
 && rm --recursive --force /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY conf/ /etc/apache2/conf-enabled/
COPY sites/ /etc/apache2/sites-enabled/
COPY htdocs/ /var/www/html/
COPY apache2/ /etc/apache2/

VOLUME /data

EXPOSE 7539

CMD ["/etc/apache2/start.sh"]
