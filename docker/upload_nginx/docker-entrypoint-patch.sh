#!/bin/bash

#set -x

chmod -R +rw /var/www/html/src/public/uploads

exec "$@"