<?php
// Event-driven button and RGB LED: the BOOT button's gestures drive the on-board WS2812, with no loop.
//
//   click        next colour
//   double click LED off (a click turns it back on)
//   hold         brighter, then back to dim
//
// The script runs once, registers the listeners and the button, and returns; the reactor sleeps between
// gestures. BOOT is GPIO0 on most boards. The on-board LED is a WS2812 in RGB order; set LED_PIN to its data
// pin (48 on the Waveshare ESP32-S3-Zero).

use Baremetal\Event;
use Baremetal\Events;
use Baremetal\Led\Driver\Ws2812;

const BUTTON  = 0;
const LED_PIN = 48;
const HUES    = [0, 30, 60, 120, 180, 240, 300];
const LEVELS  = [8, 32, 96];   // kept low: the WS2812 is dazzling

final class Click extends Event {}
final class DoubleClick extends Event {}
final class Held extends Event {}

$led = new Ws2812(LED_PIN, 1, Ws2812::RGB);
$hue = 0;
$level = 0;
$lit = true;

$show = function () use ($led, &$hue, &$level, &$lit): void {
    if ($lit) {
        $led->hsv(0, HUES[$hue], 255, LEVELS[$level]);
    } else {
        $led->pixel(0, 0, 0, 0);
    }
    $led->show();
    printf("LED %s, hue %d, level %d\n", $lit ? 'on' : 'off', HUES[$hue], LEVELS[$level]);
};

Events::listen(Click::class, function () use (&$hue, &$lit, $show): void {
    $hue = $lit ? ($hue + 1) % count(HUES) : $hue;
    $lit = true;
    $show();
});
Events::listen(DoubleClick::class, function () use (&$lit, $show): void {
    $lit = false;
    $show();
});
Events::listen(Held::class, function () use (&$level, &$lit, $show): void {
    $level = ($level + 1) % count(LEVELS);
    $lit = true;
    $show();
});

// With 'double' wired, a click waits up to 300 ms for a second one (the doubleMs option changes the window).
watch_button(BUTTON, ['click' => Click::class, 'double' => DoubleClick::class, 'held' => Held::class]);
$show();
echo "button-led-events: click, double-click or hold BOOT\n";
