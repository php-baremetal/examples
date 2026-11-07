<?php
// Follow the Wi-Fi station with events instead of polling it.
//
// Copy .env.example to .env and set WIFI_SSID / WIFI_PASSWORD: the .env is read at build time and baked into
// the firmware, then read here as $_ENV, kept out of the source and out of git. The password is never printed.
// The script registers the listeners, starts a scan that does not block, and returns; once the scan is done the
// connect starts, also without blocking (the driver refuses a scan and a connect at the same time). The reactor
// delivers the events as the radio produces them. Needs php-baremetal/ext-wifi and ext-events.

use Baremetal\Event;
use Baremetal\Events;
use Baremetal\Wifi\{StateChanged, Online, ConnectFailed, Disconnected, ScanDone};

final class Tick extends Event {}

$ssid = $_ENV['WIFI_SSID'] ?? '';
$pass = $_ENV['WIFI_PASSWORD'] ?? '';   // empty for an open network

if (!wifi_available()) {
    echo "the Wi-Fi radio did not come up\n";
    return;
}

wifi_events();

// Starts the connect once, after the first scan (or at once when the scan did not start).
$connect = function () use ($ssid, $pass): void {
    static $started = false;
    if ($started) {
        return;
    }
    $started = true;
    if ($ssid === '') {
        echo "no WIFI_SSID set -- copy .env.example to .env and fill it in to connect\n";
        return;
    }
    echo "connecting to '$ssid'...\n";
    // Retries forever; two password refusals in a row give up at once.
    if (!wifi_connect($ssid, $pass !== '' ? $pass : null, wait: false, retries: -1)) {
        echo "the connect did not start\n";
    }
};

Events::listen(StateChanged::class, function (StateChanged $e): void {
    printf("state: %s -> %s%s\n", $e->previous->value, $e->state->value,
           $e->ssid !== null ? " ({$e->ssid})" : '');
});

Events::listen(ScanDone::class, function (ScanDone $e) use ($connect): void {
    printf("scan: %d networks\n", count($e->networks));
    foreach ($e->networks as $ap) {
        printf("  %-32s ch%-3d %4d dBm\n", $ap['ssid'] !== '' ? $ap['ssid'] : '(hidden)', $ap['channel'], $ap['rssi']);
    }
    $connect();
});

Events::listen(Online::class, function (Online $e): void {
    printf("online on '%s', IP %s\n", $e->ssid, $e->ip);
});

Events::listen(ConnectFailed::class, function (ConnectFailed $e): void {
    printf("cannot join '%s': %s (code %d)\n", $e->ssid, $e->reason->value, $e->code);
});

Events::listen(Disconnected::class, function (Disconnected $e): void {
    printf("disconnected: %s (code %d)%s\n", $e->reason->value, $e->code, $e->retrying ? ', retrying' : '');
});

Events::listen(Tick::class, function (): void {
    printf("every 5 s: %s, rssi %s\n", wifi_state()->value, wifi_rssi() ?? 'n/a');
});

if (!wifi_scan(wait: false)) {
    echo "the scan did not start\n";
    $connect();
}

every(5000, Tick::class);
