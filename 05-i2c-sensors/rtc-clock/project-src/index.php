<?php
// A wall clock driven by the reactor: a 1 Hz timer reads the PCF85063 RTC and prints the time with
// its weekday. The RTC keeps time across reboots (and across power loss on a coin cell), so after the
// first set the clock reads correctly on every boot. Weekday is computed in PHP (no ext/date needed).

use Baremetal\Event;
use Baremetal\Events;
use Baremetal\I2c\Bus;
use Baremetal\I2c\Driver\Pcf85063;

final class Tick extends Event {}

$rtc = new Pcf85063(new Bus(sda: 11, scl: 10));

if (!$rtc->now()['valid']) {
    $rtc->set(2026, 10, 12, 9, 0, 0);   // seed once on a fresh (power-lost) RTC
    echo "RTC was unset -> seeded 2026-10-12 09:00:00\n";
}

function weekday(int $y, int $m, int $d): string
{
    if ($m < 3) { $m += 12; $y--; }                 // Zeller's congruence
    $k = $y % 100;
    $j = intdiv($y, 100);
    $h = ($d + intdiv(13 * ($m + 1), 5) + $k + intdiv($k, 4) + intdiv($j, 4) + 5 * $j) % 7;
    return ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'][$h];
}

Events::listen(Tick::class, function (Tick $e) use ($rtc): void {
    $t = $rtc->now();
    printf("%s %04d-%02d-%02d %02d:%02d:%02d\n",
        weekday($t['year'], $t['month'], $t['day']),
        $t['year'], $t['month'], $t['day'], $t['hour'], $t['minute'], $t['second']);
});

every(1000, Tick::class);

echo "rtc-clock: the reactor ticks once a second and reads the PCF85063\n";
