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

COPY htdocs/ /var/www/html/
COPY apache2/ /etc/apache2/

EXPOSE 7539

CMD ["/etc/apache2/start.sh"]
