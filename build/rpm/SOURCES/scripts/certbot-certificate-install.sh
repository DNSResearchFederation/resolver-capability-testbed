#!/bin/bash
DOMAIN=$(expr match "$CERTBOT_DOMAIN" '.*\.\(.*\..*\)')
printf "_acme-challenge.$CERTBOT_DOMAIN. 600 IN TXT %s\n" "$CERTBOT_VALIDATION" >> /var/named/resolvertest/$DOMAIN.conf;
systemctl reload named.service > /dev/null;
sleep 20;
