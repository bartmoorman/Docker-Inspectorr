#!/bin/bash
export PMS_APPLICATIOM_SUPPORT_DIR="${PMS_APPLICATIOM_SUPPORT_DIR:-/data/Library/Application Support}"
export PMS_DATABASE_DIR="${PMS_DATABASE_DIR:-Plex Media Server/Plug-in Support/Databases}"
export PMS_DATABASE_FILE="${PMS_DATABASE_FILE:-com.plexapp.plugins.library.db}"

if [ -f "/data/${PMS_DATABASE_FILE}" ]; then
    chown www-data: /data
elif [ -f "${PMS_APPLICATIOM_SUPPORT_DIR}/${PMS_DATABASE_DIR}/${PMS_DATABASE_FILE}" ]; then
    touch "/tmp/${PMS_DATABASE_FILE}"
    mount --bind "${PMS_APPLICATIOM_SUPPORT_DIR}/${PMS_DATABASE_DIR}/${PMS_DATABASE_FILE}" "/tmp/${PMS_DATABASE_FILE}"

    if [ -f "${PMS_APPLICATIOM_SUPPORT_DIR}/${PMS_DATABASE_DIR}/${PMS_DATABASE_FILE}-wal" ]; then
        touch "/tmp/${PMS_DATABASE_FILE}-wal"
        mount --bind "${PMS_APPLICATIOM_SUPPORT_DIR}/${PMS_DATABASE_DIR}/${PMS_DATABASE_FILE}-wal" "/tmp/${PMS_DATABASE_FILE}-wal"
    fi
fi

if [ ! -d /config/httpd/ssl ]; then
    mkdir --parents /config/httpd/ssl
    ln --symbolic --force /etc/ssl/certs/ssl-cert-snakeoil.pem /config/httpd/ssl/plexindexstatus.crt
    ln --symbolic --force /etc/ssl/private/ssl-cert-snakeoil.key /config/httpd/ssl/plexindexstatus.key
fi

exec $(which apache2ctl) \
    -D FOREGROUND \
    -D ${HTTPD_SECURITY:-HTTPD_SSL} \
    -D ${HTTPD_REDIRECT:-HTTPD_REDIRECT_SSL}
