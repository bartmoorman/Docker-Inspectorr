#!/bin/bash
PMS_APPLICATION_SUPPORT_DIR="${PMS_APPLICATION_SUPPORT_DIR:-/data/Library/Applicaton Support}"
PMS_DATABASE_DIR="${PMS_DATABASE_DIR:-Plex Media Server/Plug-in Support/Databases}"

plexGid=$(stat -c '%g' "${PMS_APPLICATION_SUPPORT_DIR}/${PMS_DATABASE_DIR}")
if [ ! getent group ${plexGid} ]; then
    groupadd -g ${plexGid} plexindexstatus
fi

plexUid=$(stat -c '%u' "${PMS_APPLICATION_SUPPORT_DIR}/${PMS_DATABASE_DIR}")
if [ ! getent passwd $plexUid} ]; then
    useradd -u ${plexUid} -g ${plexGid} -d /data -s /bin/false plexindexstatus
fi

usermod -a -G ${plexGid} www-data
chmod 664 "${PMS_APPLICATION_SUPPORT_DIR}/${PMS_DATABASE_DIR}/com.plexapp.plugins.library.db-shm"

if [ ! -d /config/httpd/ssl ]; then
    mkdir --parents /config/httpd/ssl
    ln --symbolic --force /etc/ssl/certs/ssl-cert-snakeoil.pem /config/httpd/ssl/plexindexstatus.crt
    ln --symbolic --force /etc/ssl/private/ssl-cert-snakeoil.key /config/httpd/ssl/plexindexstatus.key
fi

exec $(which apache2ctl) \
    -D FOREGROUND \
    -D ${HTTPD_SECURITY:-HTTPD_SSL} \
    -D ${HTTPD_REDIRECT:-HTTPD_REDIRECT_SSL}
