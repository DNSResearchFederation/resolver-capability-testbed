if ! ps -ef | grep logger-bind ; then
  tail -f /var/named/resolvertest/named.log | php /usr/local/src/resolvertest/src/logger-bind.php;
fi