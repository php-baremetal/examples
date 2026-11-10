# slate-gauge

A gauge moved by the tilt of the board, with the Slate profiler on: frames per second on the screen and a line of
timings per second on the console.

## What it shows

- A component of your own: `Gauge extends Group` keeps an `Arc` (with a gradient), a `Text` and a `setValue()`
  method. The scene creates it like any element.
- The IMU as a source of events: the QMI8658 is polled on core 1 at 100 Hz (`$imu->poll(hz: 100, ...,
  every: 10)`), and `SamplesReady` reaches the listener on core 0 ten times a second. The listener takes the
  newest sample, smooths the left-right tilt and calls `setValue()`: that is the only drawing code.
- A constant motion: a dot orbits the edge of the panel, moved by a 60 Hz `TimerEvent`. While it runs the scene
  changes at the panel's pace, so the frames per second the profiler shows are those of a screen in motion.
- The `slate-debug` option: `"options": { "slate-debug": true }` in `extra.baremetal` of `composer.json`. It builds
  the profiler; without it the profiler is not compiled and costs nothing.

## Run it

```sh
composer install
phpbm flash
phpbm monitor
```

## What to look at

- Tilt the board left and right: the arc follows and the number changes.
- Near the top of the screen a counter shows the frames per second. With the dot orbiting it reads 60, the panel's
  refresh rate (59.8 on average over a minute on the Waveshare ESP32-S3-Touch-LCD-1.85B).
- On the console, once a second, a line like this one:

```text
slate: 60.0 fps | frame 9.0 ms (compose 1.2, wait 7.8) | dirty 1% in 1 rects | 8 elements, 2 drawn, 0 culled | cache 0 hit 0 miss | cmds 133
```

  `fps` is the frames composed in the last second; `frame` is the average time of a frame, split into `compose`
  (drawing) and `wait` (waiting for the panel); `dirty`
  is the share of the screen composed per frame and in how many rectangles; the other fields count elements,
  caches and the commands received from PHP. The line above was measured on this board with the board held still
  (only the dot moving): a frame redraws about 1 % of the screen and composes in about 1.2 ms. Here the `wait` is
  not the bus: each band waits for the panel's refresh to pass it, so that the dot is never shown torn.
- To see a still screen, remove the `every(1000 / 60, Orbit::class)` line and hold the board still: the line reads
  `0.0 fps`, because Slate composes no frame when nothing changes.

## Other boards

`composer.json` targets `waveshare-esp32-s3-touch-lcd-1.85b`, whose IMU is on the board's I2C bus (SDA 11, SCL 10).
With another IMU, change the `Qmi8658` line: `SamplesReady` is the same event for every polled IMU.
