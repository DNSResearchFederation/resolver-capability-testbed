<?php

namespace ResolverTest\Services\Util;

class IPAddressUtils {

    public static function anonymiseIP($ipAddress) {

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {

            $components = explode(".", $ipAddress);
            array_pop($components);

            return implode(".", $components) . ".0/24";

        } else if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {

            $components = explode(":", $ipAddress);
            $components = array_slice($components, 0, 3);

            return implode(":", $components) . "::/48";

        } else {
            return $ipAddress;
        }
    }

}