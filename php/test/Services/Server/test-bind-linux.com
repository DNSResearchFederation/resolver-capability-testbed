$ORIGIN testdomain.com.
@                      3600 SOA   ns1.testdomain.com. (
                              zone-admin.testdomain.com. ; address of responsible party
                              2023061509                 ; serial number
                              3600                       ; refresh period
                              600                        ; retry period
                              604800                     ; expire time
                              1800                     ) ; minimum ttl
                      86400 NS    ns1.testdomain.com.
                      86400 NS    ns2.testdomain.com.
                        300 A     1.2.3.4
                        200 AAAA  2001::1234
                        250 MX    mail.testdomain.com.