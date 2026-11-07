# gpio-multi

Two pins watched with a single `watch_gpios()` call, in the `event-driven` runtime. Each press arrives as a
typed event that says which pin fired, at what level, and when.

## What it shows

- `watch_gpios(array $pins, string $event)`: one call registers a list of GPIOs (0 to 63). Every pin is
  watched on its falling edge, with a debounce of 200 ms per pin, and all of them deliver the same event class.
- `PinEvent`: the event class passed to `watch_gpios()` must extend `Baremetal\PinEvent`; here
  `final class Key extends PinEvent {}`. `$e->pin` is the GPIO, `$e->level` the level read at the edge, and
  `$e->time` the instant of the edge in nanoseconds, on the same clock as `hrtime(true)`.
- Delivery latency: the listener prints `hrtime(true) - $e->time` in microseconds, the time from the edge to the
  moment the listener runs.
- No `loop()`: the script runs once and returns, and the reactor sleeps between presses.

## Run it

```sh
composer install
phpbm flash
```

## Pins

`composer.json` targets `waveshare-esp32-s3-touch-lcd-1.85b`.

| GPIO | What it is |
| --- | --- |
| 0 | The BOOT button. It pulls the pin to GND when pressed. |
| 44 | The RX pin of the UART header. It is not used by any block of the board (display, I2C, microSD) and the console runs on USB-JTAG, so the pin is free. |

To trigger GPIO 44, wire it to GND with a jumper wire and take the wire away again. The pins are pulled up, so
the falling edge is the contact with GND.

## What you see

Each press prints a line on the serial console:

```text
gpio-multi: press BOOT (GPIO 0) or wire GPIO 44 to GND
GPIO 0 level 0  latency <n> us
GPIO 44 level 0  latency <n> us
```

The numbers vary from board to board. A second press on the same pin within 200 ms is ignored.

## Other boards

Change `PINS` to GPIOs that are free on your board, and set `board` in `composer.json`.
