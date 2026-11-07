# timer-control

A repeating timer that the BOOT button controls, in the `event-driven` runtime. A click stops or restarts the
timer, a double click doubles its period.

## What it shows

- `Timer`: `every(int|float $ms, string $event)` returns a `Baremetal\Timer`, a handle on the running timer. The
  event class must extend `Baremetal\TimerEvent`; here `final class Blink extends TimerEvent {}`.
- `stop()`, `start()` and `running()`: `stop()` halts the deliveries (events already queued are still
  delivered), `start()` resumes them, `running()` tells which of the two holds. Stopping a stopped timer, or
  starting a running one, does nothing.
- `setPeriod()` and `period()`: change the period in milliseconds (at least 0.1) while the timer keeps running,
  and read it back as a float.
- `watch_button(int $pin, array $on)`: the button on GPIO 0 delivers one event class per gesture. Here
  `'click'` maps to `Click` and `'double'` to `DoubleClick`, both extending `Baremetal\PinEvent`.
- No `loop()`: the script runs once and returns, and the reactor sleeps between events.

## Run it

```sh
composer install
phpbm flash
```

## Pins

`composer.json` targets `waveshare-esp32-s3-touch-lcd-1.85b`. The BOOT button is GPIO 0; it pulls the pin to
GND when pressed.

## What you see

At start the timer ticks every 500 ms on the serial console:

```text
timer-control: BOOT click stops/starts the timer, double click doubles its period
tick  (period 500 ms)
tock  (period 500 ms)
```

- A click prints `timer stopped`, and the ticks stop. The next click prints `timer started` and they resume.
- A double click prints `period 1000 ms`, then `2000 ms`, `4000 ms`, and back to `500 ms`.

## Other boards

Set `board` in `composer.json`, and change the pin in `watch_button()` to a button that is free on your board.
