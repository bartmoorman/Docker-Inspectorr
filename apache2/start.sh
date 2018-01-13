#!/bin/bash
chown www-data: /data

if [ ! -d /config/httpd/ssl ]; then
    mkdir --parents /config/httpd/ssl
    ln --symbolic --force /etc/ssl/certs/ssl-cert-snakeoil.pem /config/httpd/ssl/plexindexstatus.crt
    ln --symbolic --force /etc/ssl/private/ssl-cert-snakeoil.key /config/httpd/ssl/plexindexstatus.key
fi

exec $(which apache2ctl) \
    -D FOREGROUND \
    -D ${HTTPD_SECURITY:-HTTPD_SSL} \
    -D ${HTTPD_REDIRECT:-HTTPD_REDIRECT_SSL}
