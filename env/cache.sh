#!/bin/bash

rm -rf $1/app/cache/*
rm -rf $1/app/logs/*

HTTPDUSER=`ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX $1/app/cache $1/app/logs
setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX $1/app/cache $1/app/logs
