### Usage
```
docker run \
--detach \
--name inspectorr \
--publish 7539:7539 \
--env "HTTPD_SERVERNAME=**sub.do.main**" \
--volume inspectorr-config:/config \
--volume plex-config:/data:ro \
bmoorman/inspectorr:latest
```
