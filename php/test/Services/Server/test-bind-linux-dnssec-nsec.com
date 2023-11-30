$ORIGIN testdomain.com.
@   3600 SOA  ns1.testdomain.com.  hostmaster.testdomain.com. (
        2023061509        ; serial number
        3600              ; refresh period
        600               ; retry period
        604800            ; expire time
        1800            ) ; minimum ttl

        86400   IN   NS   ns1.testdomain.com.
        86400   IN   NS   ns2.testdomain.com.
@       600     IN   A    1.2.3.4
this   300   IN   A   1.2.3.4
that   200   IN   AAAA   2001::1234
   250   IN   MX   mail.testdomain.com.
www   200   IN   CNAME   testdomain.com.
testdomain.com. 3600 IN DNSKEY -f KSK -L 3600 -a RSASHA256 -b 2048 -n ZONE testdomain.com
testdomain.com. 3600 IN DNSKEY -L 3600 -a RSASHA256 -b 2048 -n ZONE testdomain.com
