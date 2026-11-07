<?php
/**
 * Two pins, one call, one event class. watch_gpios() watches every pin in the list on its falling edge
 * (with a 200 ms debounce per pin) and delivers a Key event for each press. Key extends PinEvent, so the
 * event carries $pin, $level and $time: which pin fired, its level at the edge, and when (ns, on the clock
 * of hrtime(true)).
 *
 *   GPIO 0   the BOOT button
 *   GPIO 44  the RX pin of the UART header: wire it to GND to trigger it
 *
 * The script runs once, registers the listener and the pins, and returns; the reactor sleeps between presses.
 */

use Baremetal\Events;
use Baremetal\PinEvent;

const PINS = [0, 44];

final class Key extends PinEvent {}

Events::listen(Key::class, function (Key $e): void {
    $latencyUs = (hrtime(true) - $e->time) / 1e3;   // from the edge to this listener
    printf("GPIO %d level %d  latency %.0f us\n", $e->pin, (int) $e->level, $latencyUs);
});

watch_gpios(PINS, Key::class);

echo "gpio-multi: press BOOT (GPIO 0) or wire GPIO 44 to GND\n";
