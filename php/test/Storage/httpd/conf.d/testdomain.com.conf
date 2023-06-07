<VirtualHost *:80>
  ServerName testdomain.com
  ServerAlias *.testdomain.com
  DocumentRoot Storage/httpd/www/testdomain.com
  CustomLog "|$php /usr/local/src/resolvertest/src/logger-httpd.php" "%{Host}i %h %l %u %t \"%r\" %>s %b"
</VirtualHost>