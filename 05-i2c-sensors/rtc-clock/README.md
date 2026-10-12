# rtc-clock

A wall clock in the **event-driven** model: a 1 Hz timer source (`every(1000, Tick::class)`) drives the
clock, and the listener reads the PCF85063 RTC (`$rtc->now()`) and prints the time with its weekday.
There is no `loop()` — the reactor sleeps between ticks.

The RTC keeps time across reboots and, on a coin cell, across power loss, so the time is set once (only
when the RTC reports it is unset) and is correct on every later boot. The weekday is computed in PHP
(Zeller's congruence), so no `date` extension is required.

Uses the `i2c` extension with the `pcf85063` driver. The RTC here is on the onboard bus
(`new Bus(sda: 11, scl: 10)`).

## Run

```sh
phpflash build && phpflash flash && phpflash monitor
```
