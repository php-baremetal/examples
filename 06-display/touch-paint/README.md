# touch-paint

Paint on the round panel of the Waveshare ESP32-S3-Touch-LCD-1.85B with a finger. The CST816S touch controller
raises `Pressed`, `Moved` and `Gesture` events, and the listeners draw.

## What it shows

- The `event-driven` runtime with a touch source: `index.php` runs once, registers listeners and calls
  `Cst816s::onboard()->events()`. There is no `loop()`: each report of the controller (INT line) becomes an
  event, and the reactor sleeps in between.
- `St77916::onboard()` and `Cst816s::onboard()`: the panel and the touch controller are opened with the pins of
  the board's `display`, `i2c` and `touch` blocks.
- Touch events of `php-baremetal/ext-input`: `Pressed` and `Moved` carry `$x` and `$y` in screen coordinates
  (0 to 359 on this panel); `Gesture` adds `$type`, a `GestureType` enum.
- Drawing with `fillCircle()` and a region `flush()`: each touch point draws a filled circle of radius 4 in
  the current colour and flushes only the 9 x 9 pixels around it.
- Gestures: a swipe to the right selects the next colour and a swipe to the left the previous one (white, red,
  orange, green, blue, violet, then back to white). A double tap clears the screen. Every gesture is printed on
  the serial console with `$e->type->value`, for example `gesture swipe-left at 120,200`.

## Run it

```sh
composer install
phpbm flash
phpbm monitor
```

## What to try

- Drag a finger over the screen: a line of dots follows it. A fast drag leaves gaps: `Moved` is raised at each
  report of the chip, about 20 to 65 times a second, and the example draws only the reported points.
- Swipe left and right, then draw again: the colour changes. Watch the console for the gesture names.
- Tap, hold for a second (`long-press`) and swipe up and down: they are printed only.
- Double tap: the screen is cleared. The driver turns the chip's double tap detection on by default
  (`double-tap` key of the `touch` block), so a single `tap` is printed about 260 ms after the lift, not at once.
- The chip sleeps when not touched and wakes on a touch; the first touch after a pause was read like any other in
  board runs. The INT line pulses about 20 to 65 times a second while the finger is down.
- If the dots land mirrored or swapped relative to the finger, set `mirror-x`, `mirror-y` or `swap-xy` in the
  board's `touch` block (board keys are kebab-case; the matching arguments of `new Cst816s(...)` are `mirror_x`,
  `mirror_y` and `swap_xy`).

## Other boards

`composer.json` targets `waveshare-esp32-s3-touch-lcd-1.85b`. On a board whose description has no `touch` block,
`Cst816s::onboard()` throws; build the driver with the bus and pins instead. `events()` needs the INT pin
(`int:`); without it, it throws `\ValueError`.

```php
use Baremetal\I2c\Bus;
use Baremetal\I2c\Driver\Cst816s;

$touch = new Cst816s(new Bus(sda: 11, scl: 10), int: 4, rst: 1, width: 360, height: 360);
$touch->events();
```
