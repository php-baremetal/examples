# display-hello

Draw on the round ST77916 panel of the Waveshare ESP32-S3-Touch-LCD-1.85B, then animate a square.

## What it shows

- `St77916::onboard()`: opens the panel with the pins of the board's `display` block.
- Drawing into the framebuffer (`clear`, `fillRect`, `circle`, `text` with the default font), sent to the panel
  by `flush()`.
- Region flush: `loop()` moves a square and flushes only the band it touches, then redraws the frame-rate
  line and flushes only that.
- Frame pacing: the square moves by the time that passed (120 pixels per second), and `loop()` sleeps with
  `usleep()` until the next frame is due. The deadline moves by exactly 16.7 ms each frame, so a sleep that
  runs long (`usleep()` rounds up to the 1 ms scheduler tick) is made up by the next one and the loop holds
  60 fps. The panel refreshes at about 60 Hz, so drawing faster only makes the square jump between refreshes
  instead of sliding.

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
  changes; the serial console prints it every second. It reads 60.0 once the loop has settled.

## Other boards

`composer.json` targets `waveshare-esp32-s3-touch-lcd-1.85b`. On a board whose description has no `display`
block, `onboard()` throws; build the driver with the bus and pins instead:

```php
use Baremetal\Spi\Bus;
use Baremetal\Spi\Driver\St77916;

$lcd = new St77916(new Bus(sclk: 40, mosi: 46, miso: 45, data2: 42, data3: 41), cs: 21, rst: 3, bl: 5);
```
