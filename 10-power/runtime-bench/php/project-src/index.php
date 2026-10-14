<?php
// runtime-bench, php-baremetal: four 30 s phases from power on, then the deep sleep reboots and the cycle starts again.
//   0-30 s   idle in delay()       (no power management: the CPU stays at full clock)
//   30-60 s  busy loop
//   60-90 s  sys_light_sleep(30000)
//   90-120 s sys_deep_sleep(30000) (then a reboot of ~1 s)
// Read each phase on the meter with the board on 5 V and no USB data (see ../README.md).

const PHASE = 30;

function setup(): void
{
    echo "runtime-bench (php-baremetal): idle / busy / light sleep / deep sleep, ", PHASE, " s each\n";
    echo "[IDLE]\n";
    delay(PHASE * 1000);
    echo "[BUSY]\n";
    $end = hrtime()[0] + PHASE;
    while (hrtime()[0] < $end) {
    }
    echo "[LIGHT SLEEP]\n";
    sys_light_sleep(PHASE * 1000);
    echo "[DEEP SLEEP]\n";
    sys_deep_sleep(PHASE * 1000);
}

function loop(int $tick): void
{
}
