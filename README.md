```
docker run \
--rm \
--detach \
--init \
--name plexindexstatus \
--hostname plexindexstatus \
--volume /var/lib/plexmediaserver/Library/Application\ Support/Plex\ Media\ Server/Plug-in\ Support/Databases/com.plexapp.plugins.library.db:/data/com.plexapp.plugins.library.db \
--publish 80:80 \
bmoorman/plexindexstatus
```
