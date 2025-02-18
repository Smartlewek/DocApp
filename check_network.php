<?php
function getUserIP() {
    // Sprawdzanie, czy nagłówek Cloudflare CF-Connecting-IP jest obecny
    if (!empty($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $source = "WAN";
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Sprawdzanie nagłówka X-Forwarded-For, gdy jest używane proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $source = "Proxy";
    } else {
        // Standardowe połączenie
        $ip = $_SERVER['REMOTE_ADDR'];
        $source = "LAN";
    }

    // Sprawdzenie, czy IP jest prywatny
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $private_ranges = [
            '10.0.0.0|10.255.255.255',
            '172.16.0.0|172.31.255.255',
            '192.168.0.0|192.168.255.255'
        ];

        foreach ($private_ranges as $range) {
            list($start, $end) = explode('|', $range);
            if (ip2long($ip) >= ip2long($start) && ip2long($ip) <= ip2long($end)) {
                $source = "LAN";
                break;
            }
        }
    }

    return ['ip' => $ip, 'source' => $source];
}
?>
