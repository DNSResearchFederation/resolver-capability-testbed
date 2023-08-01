<VirtualHost *:80>
  ServerName testdomain.com
  ServerAlias *.testdomain.com
  Header set Access-Control-Allow-Origin "*"
  DocumentRoot Storage/httpd/www/testdomain.com
  CustomLog "|$php /usr/local/src/resolvertest/src/logger-httpd.php" "\"%{Host}i\" %h %t \"%r\" %>s \"%{User-agent}i\""
</VirtualHost>