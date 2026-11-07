# display-hello-event-driven

The same picture and animation as `06-display/display-hello`, in the `event-driven` runtime: each frame is
drawn on the round ST77916 panel of the Waveshare ESP32-S3-Touch-LCD-1.85B, paced by the panel's TE pin
(`onVsync()`).

## What it shows

- The `event-driven` runtime: `index.php` runs once. It draws the first frame, registers a listener for the
  `Frame` event and calls `$lcd->onVsync(Frame::class)`. There is no `loop()`: the reactor delivers one `Frame`
  per panel refresh and sleeps in between.
- `St77916::onboard()`: opens the panel with the pins of the board's `display` block.
- Drawing into the framebuffer (`clear`, `fillRect`, `circle`, `text` with the default font), sent to the panel
  by `flush()`, which returns before the transfer ends: the panel reads the framebuffer while the script goes on.
- Region flush: each frame moves a square and flushes only the band it touches. The frame-rate line is
  redrawn, and flushed, only when its value changes.
- Frame pacing from the panel: the metronome is the panel's TE (tearing effect) pin, which pulses once per
  refresh (60 Hz). `onVsync()` raises a `Frame` for each pulse, so the animation follows the real scan of the
  panel instead of a timer that slowly drifts from it. The square moves by the time that passed (120 pixels
  per second), so a late event does not slow it down.
- The event payload: `Frame` extends `Baremetal\TimerEvent`. `$e->time` is the instant the tick fired, in
  nanoseconds on the same clock as `hrtime(true)`; the square moves by the difference between two ticks, so it
  does not depend on when the listener happens to run. `$e->ticks` is how many ticks the event stands for: 1, or more when the listener fell behind and ticks were
  coalesced into it.
  Once a second the listener also averages `hrtime(true) - $e->time`, the delivery latency, and the console
  line prints it in microseconds.

Compared with `display-hello`, the pacing comes from the panel's vsync instead of `usleep()` in `loop()`, and the
listener's state lives in variables that stay between events.

## Run it

```sh
composer install
phpbm flash
```

## What you see

- Dark blue background, with a row of four squares at the top: red, green, blue, white.
- An orange circle in the middle with the word `Hello` inside.
- A yellow square sliding smoothly left and right near the bottom.
- Under it, the measured frame rate (`<n> fps`), measured every second and redrawn only when the value
  changes; the serial console prints it every second, together with the average delivery latency
  (`60.0 fps  latency <n> us`). It reads 60.0.

## Other boards

`composer.json` targets `waveshare-esp32-s3-touch-lcd-1.85b`. On a board whose description has no `display`
block, `onboard()` throws; build the driver with the bus and pins instead. `onVsync()` needs the panel's TE pin
(`te:`); without it, it throws `\ValueError`.

```php
use Baremetal\Spi\Bus;
use Baremetal\Spi\Driver\St77916;

$lcd = new St77916(new Bus(sclk: 40, mosi: 46, miso: 45, data2: 42, data3: 41), cs: 21, rst: 3, bl: 5, te: 18);
```
