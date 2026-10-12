<?php
// Event-driven GPIO: the BOOT button raises a debounced interrupt that the reactor delivers as a typed
// event; the handler toggles an LED. No polling loop -- the CPU sleeps until the button is pressed.
//
//   LED + ~330 ohm resistor between GPIO2 and GND
//   BOOT button is GPIO0 on most dev boards

use Baremetal\Event;
use Baremetal\Events;

const LED = 2;

final class Pressed extends Event {}

gpio_mode(LED, GPIO_OUTPUT);
$on = false;

Events::listen(Pressed::class, function (Pressed $e) use (&$on): void {
    $on = !$on;
    gpio_write(LED, $on ? 1 : 0);
    printf("button pressed -> LED %s\n", $on ? 'on' : 'off');
});

watch_gpio(0, Pressed::class);   // GPIO0 = BOOT, falling edge, debounced by the source

echo "gpio-interrupt: press BOOT to toggle the LED; the reactor sleeps between presses\n";
