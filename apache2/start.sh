#!/bin/bash
chown www-data /data
exec $(which apache2ctl) -D FOREGROUND
