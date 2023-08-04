<VirtualHost *:80>
  ServerName testdomain.com
  ServerAlias *.testdomain.com
  Header set Access-Control-Allow-Origin "*"
  DocumentRoot Storage/httpd/www/testdomain.com
  CustomLog "|$php /usr/local/src/resolvertest/src/logger-httpd.php" "\"%{Host}i\" %h %t \"%r\" %>s \"%{User-agent}i\""
</VirtualHost>

<VirtualHost *:443>
  ServerName testdomain.com
  ServerAlias *.testdomain.com
  Header set Access-Control-Allow-Origin "*"
  DocumentRoot Storage/httpd/www/testdomain.com
  CustomLog "|$php /usr/local/src/resolvertest/src/logger-httpd.php" "\"%{Host}i\" %h %t \"%r\" %>s \"%{User-agent}i\""

  SSLEngine on
  SSLCertificateChainFile  /etc/letsencrypt/live/testdomain.com/fullchain.pem
  SSLCertificateKeyFile    /etc/letsencrypt/live/testdomain.com/privkey.pem
  SSLCertificateFile       /etc/letsencrypt/live/testdomain.com/cert.pem
</VirtualHost>