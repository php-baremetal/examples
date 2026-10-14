<?php
// A live sensor page over the board's own WiFi, in the event-driven model: the reactor runs an HTTP
// source, and each request reads the MMC5603 magnetometer and returns the current heading -- as an
// HTML page that refreshes itself, or as JSON at /heading. Join the access point, open http://<ap-ip>/.
//
// This ties three sources together -- WiFi, HTTP and a sensor -- the shape of a small real device.

use Baremetal\Events;
use Baremetal\Http\Request;
use Baremetal\Http\Response;
use Baremetal\I2c\Bus;
use Baremetal\I2c\Driver\Mmc5603;

const AP_SSID = 'php-baremetal';
const AP_PASS = 'baremetal';   // >= 8 chars for WPA2; '' for an open network

if (!wifi_available()) {
    echo "the Wi-Fi radio did not come up\n";
    return;
}

$mag = new Mmc5603(new Bus(sda: 7, scl: 8));

if (!wifi_ap_start(AP_SSID, AP_PASS !== '' ? AP_PASS : null)) {
    echo "failed to start the access point\n";
    return;
}

Events::listen(Request::class, function (Request $r) use ($mag): Response {
    [$x, $y, $z] = $mag->mag();
    $h = $mag->heading();

    return match (true) {
        $r->path === '/heading' => Response::json([
            'heading_deg' => round($h, 1),
            'field_ut'    => ['x' => round($x, 1), 'y' => round($y, 1), 'z' => round($z, 1)],
        ]),
        $r->path === '/' => Response::html(
            "<!doctype html><meta name=viewport content='width=device-width,initial-scale=1'>"
            . "<title>php-baremetal sensor</title>"
            . "<body style='font-family:sans-serif;text-align:center;margin-top:3rem'>"
            . "<h1>heading " . round($h) . "&deg;</h1>"
            . "<p>field (uT): x=" . round($x, 1) . " y=" . round($y, 1) . " z=" . round($z, 1) . "</p>"
            . "<p><a href='/heading'>/heading</a> returns JSON.</p>"
            . "<script>setTimeout(function(){location.reload()},1000)</script>"
        ),
        default => Response::notFound(),
    };
});

serve_http(80);

printf("AP '%s' up at %s -- join it and open http://%s/\n", AP_SSID, wifi_ap_ip(), wifi_ap_ip());
