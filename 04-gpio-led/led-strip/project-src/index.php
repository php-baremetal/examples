<?php
// A WS2812 strip of several pixels: a dot runs along a rainbow, then the whole strip pulses.
//
// Wire the strip's DIN to STRIP_PIN, GND to GND and 5 V to the board's 5 V pin (a few pixels at this low
// brightness are fine on USB power). Standard strips are GRB; set PIXELS to your strip's length.

use Baremetal\Led\Driver\Ws2812;

const STRIP_PIN = 1;
const PIXELS    = 8;
const BRIGHT    = 24;   // 0..255

$strip = null;

function setup(): void
{
    global $strip;
    $strip = new Ws2812(STRIP_PIN, PIXELS);   // GRB by default
    printf("led-strip: %d pixels on GPIO %d\n", $strip->count(), STRIP_PIN);
}

function loop(int $tick): void
{
    global $strip;
    $n = $strip->count();

    // A rainbow across the strip, with one white dot walking over it.
    for ($dot = 0; $dot < $n; $dot++) {
        for ($i = 0; $i < $n; $i++) {
            $strip->hsv($i, intdiv(360 * $i, $n) + $tick * 15, 255, BRIGHT);
        }
        $strip->pixel($dot, BRIGHT, BRIGHT, BRIGHT);
        $strip->show();
        delay(80);
    }

    // The whole strip in one colour, fading in and out.
    for ($v = 0; $v <= BRIGHT; $v += 2) {
        $strip->set(0, 0, $v);
        delay(15);
    }
    for ($v = BRIGHT; $v >= 0; $v -= 2) {
        $strip->set(0, 0, $v);
        delay(15);
    }
    $strip->off();
    delay(200);
}
