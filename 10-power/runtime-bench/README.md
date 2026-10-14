# runtime-bench

The same power bench in four runtimes: php-baremetal, plain C (ESP-IDF), MicroPython and TinyGo. Each one
runs fixed 30 s phases from power on, so the board's current can be read on a USB meter phase by phase.

It measures what each runtime costs on top of the chip while it waits, spins and sleeps. It is not a speed
or energy-per-task benchmark.

## Phases

| Time from power on | Phase | php-baremetal | C | MicroPython | TinyGo |
| --- | --- | --- | --- | --- | --- |
| 0-30 s | Idle | `delay()` | `vTaskDelay()` | `time.sleep()` | `time.Sleep()` |
| 30-60 s | Busy loop | `while` on `hrtime()` | `while` on `esp_timer_get_time()` | `while` on `time.ticks_ms()` | `for` on `time.Now()` |
| 60-90 s | Light sleep | `sys_light_sleep()` | `esp_light_sleep_start()` | `machine.lightsleep()` | not available |
| 90-120 s | Deep sleep | `sys_deep_sleep()` | `esp_deep_sleep()` | `machine.deepsleep()` | not available |

The deep sleep ends in a reboot, so the cycle repeats on its own. TinyGo has no light or deep sleep for the
ESP32-S3, so its bench alternates idle and busy. No runtime uses power management: idle is a plain wait at
full clock.

## Results

ESP32-S3 SuperMini (ESP32-S3FH4R2: 4 MB flash, 2 MB quad PSRAM), CPU at 160 MHz in every runtime, powered
from a USB charger through an AVHzY CT-3 meter, average current of each phase:

| Phase | php-baremetal | C (ESP-IDF 6.1) | MicroPython 1.29.0 | TinyGo 0.44.0 |
| --- | --- | --- | --- | --- |
| Idle | 31.6 mA | 31.3 mA | 32.7 mA | 74.0 mA |
| Busy loop | 50.3 mA | 45.1 mA | 51.3 mA | 75.3 mA |
| Light sleep | 1.6 mA | 1.6 mA | 1.6 mA | not available |
| Deep sleep | 0.5 mA | 0.5 mA | 0.5 mA | not available |

These are whole-board figures, including the regulator and the other components. TinyGo stays near its busy
current while idle; the cause was not investigated.

## Running it

Measure from an external 5 V supply with no USB data: on this board a USB host on the console port changes
what the chip does (php-baremetal keeps it out of automatic light sleep). Flash each bench with the board
connected to the PC directly, then move it to the charger and meter. A USB meter in the data path can make
the flash fail.

### php-baremetal (`php/`)

```sh
cd php
composer install
phpbm flash
```

### C (`c/`)

ESP-IDF 6.1. `sdkconfig.defaults` matches the php-baremetal firmware: 160 MHz, quad PSRAM on, USB-Serial-JTAG
console, no power management.

```sh
cd c
idf.py set-target esp32s3
idf.py build flash
```

### MicroPython (`micropython/`)

The official `ESP32_GENERIC_S3` firmware, which detects the quad PSRAM on its own. Write the firmware at
`0x0`, then copy `main.py`, which runs at every boot:

```sh
esptool --chip esp32s3 erase-flash
esptool --chip esp32s3 write-flash 0x0 ESP32_GENERIC_S3-20260824-v1.29.0.bin
mpremote fs cp micropython/main.py :main.py + reset
```

### TinyGo (`tinygo/`)

TinyGo starts the ESP32-S3 at 240 MHz; `main.go` sets 160 MHz like the others. The image has no bootloader
and goes at `0x0`:

```sh
cd tinygo
tinygo build -target esp32s3-supermini -o runtime-bench.bin .
esptool --chip esp32s3 write-flash 0x0 runtime-bench.bin
```
