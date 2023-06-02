Name: resolvertest
Version: 0.0.1
Release: 0.0.2
Summary: Resolver Capability Testing Framework
License: MIT
BuildArch: noarch
Requires: httpd, bind, php-cli, php-json, crontabs, certbot

%description
Flexible resolver capability testing framework for DNS resolvers. Centos Version

%install
mkdir -p %{buildroot}/usr/local/src/resolvertest/src
cp -r %{buildroot}/../../SOURCES/php/src/*  %{buildroot}/usr/local/src/resolvertest/src/
cp -r %{buildroot}/../../SOURCES/php/composer.json %{buildroot}/usr/local/src/resolvertest/
rm %{buildroot}/usr/local/src/resolvertest/src/resolvertest.php
chmod 755 %{buildroot}/usr/local/src/resolvertest/src/resolvertest-linux.php
cp -r %{buildroot}/usr/local/src/resolvertest/src/Config/config-fedora.txt %{buildroot}/usr/local/src/resolvertest/src/Config/config.txt
mkdir -p %{buildroot}/etc/cron.d
cp -r %{buildroot}/../../SOURCES/cron/resolvertest-scheduler %{buildroot}/etc/cron.d/

%files
/usr/local/src/resolvertest/src
/usr/local/src/resolvertest/composer.json
/etc/cron.d/resolvertest-scheduler

%post
dnf module enable php:remi-8.1 -y
php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');";php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
grep -qxF 'include "/etc/named.resolvertest.zones";' /etc/named.conf || echo 'include "/etc/named.resolvertest.zones";' >> /etc/named.conf
(cd /usr/local/src/resolvertest; rm -f composer.lock; /usr/local/bin/composer install; /usr/local/bin/composer update)
rm -f /usr/local/bin/resolvertest
ln -s /usr/local/src/resolvertest/src/resolvertest-linux.php /usr/local/bin/resolvertest
mkdir -p /var/lib/resolvertest/tests
chmod 777 /var/lib/resolvertest/tests
mkdir -p /usr/local/etc/resolvertest
chmod 777 /usr/local/etc
mkdir -p /var/named/resolvertest
chmod 777 /var/named/resolvertest
touch /etc/named.resolvertest.zones
chmod 777 /etc/named.resolvertest.zones
service named restart
service httpd restart
service crond restart