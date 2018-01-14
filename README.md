### Usage
```
docker run \
--rm \
--detach \
--init \
--cap-add SYS_ADMIN \
--security-opt apparmor=unconfined \
--name plexindexstatus \
--hostname plexindexstatus \
--volume plexindexstatus-config:/config \
--volume plex-config:/data:ro \
--publish 7539:7539 \
--env "HTTPD_SERVERNAME=**sub.do.main**" \
bmoorman/plexindexstatus
```
