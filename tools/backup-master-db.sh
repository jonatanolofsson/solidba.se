#!/bin/sh
DATE="`date +%F`"
touch "yweb-$DATE.sql.gz"
chmod 600 "yweb-$DATE.sql.gz"
mysqldump --ignore-table yweb.config --add-drop-table -i -c -p yweb | gzip -9 -c > "yweb-$DATE.sql.gz"

