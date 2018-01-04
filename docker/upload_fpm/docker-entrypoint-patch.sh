#!/bin/bash

#set -x

(cd /var/www/html/src; [ `ls public/uploads|wc -w` -ne 0 ]||cp -r uploads/* public/uploads)

exec "$@"