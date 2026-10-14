<?php
// power-bench (Waveshare ESP32-S3-Zero): cycles three states, 5 s each, with a serial marker per state,
// so each one's current can be read on the meter. Deep sleep reboots, so the cycle repeats on its own.
//   ACTIVE      - CPU awake (busy)
//   LIGHT SLEEP - sys_light_sleep(), timer wake (PSRAM retained)
//   DEEP SLEEP  - sys_deep_sleep(), timer wake (full reboot)
// Take the readings on external 5 V with the USB data cable unplugged: the console keeps the chip
// awake while it is connected.

use Baremetal\Event;
use Baremetal\Events;

const SECS = 5;

final class Cycle extends Event {}

echo "power-bench (Waveshare ESP32-S3-Zero): active / light sleep / deep sleep, 5 s each.\n";
if (sys_wake_cause() !== 'none') {
    echo "(back from deep sleep -> new cycle)\n";
}

Events::listen(Cycle::class, function (): void {
    echo "[ACTIVE] " . SECS . "s -- read current\n";
    $t = microtime(true);
    while (microtime(true) - $t < SECS) {
        // CPU awake and busy
    }

    echo "[LIGHT SLEEP] " . SECS . "s -- read current\n";
    sys_light_sleep(SECS * 1000);      // timer-only wake

    echo "[DEEP SLEEP] " . SECS . "s -- read current (then reboot)\n";
    usleep(200000);
    sys_deep_sleep(SECS * 1000);       // timer-only wake -> full reboot -> cycle repeats
});

Events::now(new Cycle());
