#!/bin/bash
printf "_acme-challenge 600 IN TXT %s" "$CERTBOT_VALIDATION" >> /var/named/resolvertest/$CERTBOT_DOMAIN.conf;
systemctl reload named.service > /dev/null;
sleep 20;
