Name: resolvertest
Version: 0.0.1
Release: 0.0.2
Summary: Resolver Capability Testing Framework
License: MIT
BuildArch: noarch
Requires: httpd, bind9-next, php-cli, php-pdo, composer, certbot, crontabs, sqlite

%description
Flexible resolver capability testing framework for DNS resolvers.

%install
mkdir -p %{buildroot}/usr/local/src/resolvertest/src
cp -r %{buildroot}/../../SOURCES/php/src/*  %{buildroot}/usr/local/src/resolvertest/src/
cp -r %{buildroot}/../../SOURCES/php/composer.json %{buildroot}/usr/local/src/resolvertest/
rm %{buildroot}/usr/local/src/resolvertest/src/resolvertest.php
chmod 755 %{buildroot}/usr/local/src/resolvertest/src/resolvertest-linux.php
chmod 755 %{buildroot}/usr/local/src/resolvertest/src/logger-httpd.php
cp -r %{buildroot}/usr/local/src/resolvertest/src/Config/config-fedora.txt %{buildroot}/usr/local/src/resolvertest/src/Config/config.txt
mkdir -p %{buildroot}/etc/cron.d
cp -r %{buildroot}/../../SOURCES/cron/resolvertest-scheduler %{buildroot}/etc/cron.d/
mkdir -p %{buildroot}/usr/local/src/resolvertest/scripts
cp -r %{buildroot}/../../SOURCES/scripts/* %{buildroot}/usr/local/src/resolvertest/scripts/

%files
/usr/local/src/resolvertest/scripts
/usr/local/src/resolvertest/src
/usr/local/src/resolvertest/composer.json
/etc/cron.d/resolvertest-scheduler

%post
grep -qxF 'include "/etc/named.resolvertest.zones";' /etc/named.conf || echo 'include "/etc/named.resolvertest.zones";' >> /etc/named.conf
sed -i -E "s/port 53 \{.+\};/port 53 \{ any; \};/g" /etc/named.conf
sed -i -E "s/recursion yes;/recursion no;/" /etc/named.conf
sed -i -E "s/allow-query     \{.+\};/allow-query     { any; };/g" /etc/named.conf
sed -i -E "s|logging \{|logging {\n    channel queries_log {\n        file \"/var/named/resolvertest/named.log\";\n        print-time yes;\n        print-category yes;\n    };\n     category queries { queries_log; };|" /etc/named.conf
export COMPOSER_ALLOW_SUPERUSER=1
(cd /usr/local/src/resolvertest; rm -f composer.lock; composer install; composer update)
mkdir -p /var/lib/resolvertest/logs
chmod -R 777 /var/lib/resolvertest
if [ ! -f /var/lib/resolvertest/resolvertest.db ]; then
  (cd /usr/local/src/resolvertest; composer install-database)
  chmod 777 /var/lib/resolvertest/resolvertest.db
  touch /var/lib/resolvertest/db.log
  chmod 777 /var/lib/resolvertest/db.log
fi
rm -f /usr/local/bin/resolvertest
ln -s /usr/local/src/resolvertest/src/resolvertest-linux.php /usr/local/bin/resolvertest
mkdir -p /usr/local/etc/resolvertest
chmod 777 /usr/local/etc
mkdir -p /var/named/resolvertest
chmod 777 /var/named/resolvertest
touch /etc/named.resolvertest.zones
chmod 777 /etc/named.resolvertest.zones
chcon -R -t httpd_sys_rw_content_t /var/lib/resolvertest/
chmod 777 /usr/local/src/resolvertest/scripts/certbot-certificate-install.sh
chmod 777 /usr/local/src/resolvertest/scripts/certbot-certificate-install-dnssec.sh
setsebool -P httpd_can_network_connect 1
service named restart
service httpd restart
service crond restart