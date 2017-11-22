```
docker run \
--rm \
--detach \
--init \
--name plexindexstatus \
--hostname plexindexstatus \
--volume /var/lib/plexmediaserver/Library/Application\ Support/Plex\ Media\ Server/Plug-in\ Support/Databases/com.plexapp.plugins.library.db:/data/com.plexapp.plugins.library.db:ro \
--publish 7539:7539 \
--env "HTTPD_SERVERNAME=**sub.do.main**" \
bmoorman/plexindexstatus
```
