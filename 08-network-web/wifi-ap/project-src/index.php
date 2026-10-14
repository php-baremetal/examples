<?php
// Turn the board into a WiFi access point, from PHP -- it creates its own network.
//
// Connect a phone or laptop to the network below; the board runs its own DHCP server, so your
// device gets an IP automatically. Needs php-baremetal/ext-wifi (WiFi-capable boards).

const AP_SSID = 'php-baremetal';
const AP_PASS = 'baremetal';   // >= 8 chars for WPA2; '' (empty) for an open network

function setup(): void
{
    if (!wifi_available()) {
        echo "the Wi-Fi radio did not come up\n";
        return;
    }

    if (wifi_ap_start(AP_SSID, AP_PASS !== '' ? AP_PASS : null)) {
        echo "access point '" . AP_SSID . "' is up at " . wifi_ap_ip() . "\n";
        echo (AP_PASS !== '' ? "join it with password '" . AP_PASS . "'" : "it is open, just join it") . "\n";
    } else {
        echo "failed to start the access point\n";
    }
}

function loop(int $tick): void
{
    echo "tick $tick: " . wifi_ap_clients() . " device(s) connected to " . AP_SSID . "\n";
    delay(5000);
}
