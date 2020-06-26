### Docker Run
```
docker run \
--detach \
--name inspectorr \
--restart unless-stopped \
--publish 7539:7539 \
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
    restart: unless-stopped
    ports:
      - "7539:7539"
    volumes:
      - inspectorr-config:/config
      - plex-config:/data:ro

volumes:
  inspectorr-config:
```

### Environment Variables
|Variable|Description|Default|
|--------|-----------|-------|
|TZ|Sets the timezone|`America/Denver`|
|HTTPD_SERVERNAME|Sets the vhost servername|`localhost`|
|HTTPD_PORT|Sets the vhost port|`7539`|
|HTTPD_SSL|Set to anything other than `SSL` (e.g. `NO_SSL`) to disable SSL|`SSL`|
|HTTPD_REDIRECT|Set to anything other than `REDIRECT` (e.g. `NO_REDIRECT`) to disable SSL redirect|`REDIRECT`|
|PMS_CONFIG_DIR|Sets the Plex config directory|`/data`|
|PMS_APPLICATIOM_SUPPORT_DIR|Sets the Plex Application Support directory (relative to **PMS_CONFIG_DIR**)|`Library/Application Support`|
|PMS_DATABASE_DIR|Sets the Plex Database direcotry (relative to **PMS_APPLICATIOM_SUPPORT_DIR**)|`Plex Media Server/Plug-in Support/Databases`|
|PMS_DATABASE_FILE|Sets the Plex DB file (relative to **PMS_DATABASE_DIR**)|`com.plexapp.plugins.library.db`|
