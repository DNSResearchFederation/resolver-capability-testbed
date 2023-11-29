#!/bin/bash
DOMAIN=$(expr match "$CERTBOT_DOMAIN" '.*\.\(.*\..*\)')
printf "_acme-challenge.$CERTBOT_DOMAIN. 600 IN TXT %s\n" "$CERTBOT_VALIDATION" >> /var/named/resolvertest/$CERTBOT_DOMAIN.conf;
/usr/sbin/dnssec-signzone -K /var/named/resolvertest/dnssec/$CERTBOT_DOMAIN -d /var/named/resolvertest/dnssec/$CERTBOT_DOMAIN -N INCREMENT -o $CERTBOT_DOMAIN /var/named/resolvertest/$CERTBOT_DOMAIN.conf;
chown 777 /var/named/resolvertest/$CERTBOT_DOMAIN.conf.signed
systemctl reload named.service > /dev/null;
sleep 20;
