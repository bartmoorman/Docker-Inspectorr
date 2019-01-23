### Docker Run
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

### Docker Compose
```
version: "3.7"
services:
  inspectorr:
    image: bmoorman/inspectorr:latest
    container_name: inspectorr
    ports:
      - "7539:7539"
    environment:
      - HTTPD_SERVERNAME=**sub.do.main**
    volumes:
      - inspectorr-config:/config
      - plex-config:/data:ro

volumes:
  inspectorr-config:
```
