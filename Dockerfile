FROM bmoorman/ubuntu

ENV HTTPD_SERVERNAME="localhost"

ARG DEBIAN_FRONTEND="noninteractive"

RUN apt-get update && \
    apt-get dist-upgrade --yes && \
    apt-get install --yes --no-install-recommends apache2 libapache2-mod-php php-sqlite3 && \
    apt-get autoremove --yes --purge && \
    apt-get clean && \
    rm --recursive --force /var/lib/apt/lists/* /tmp/* /var/tmp/*

VOLUME /data

COPY conf/ /etc/apache2/conf-enabled/
COPY htdocs/ /var/www/html/

CMD ["apache2ctl", "-D", "FOREGROUND"]

EXPOSE 80
