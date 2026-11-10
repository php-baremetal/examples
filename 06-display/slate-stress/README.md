# slate-stress

A busy scene: many things move at once on the round panel of the Waveshare ESP32-S3-Touch-LCD-1.85B, while the IMU
is polled, the touch screen is read and Wi-Fi connects in the background. The Slate profiler is on, so the frames
per second are on the screen and a line of timings per second is on the console.

## What moves

All of these are set by one 60 Hz `TimerEvent` listener:

- 12 translucent dots (alpha `0xC0`) orbiting the centre at different radii and speeds;
- a card with a `LinearGradient` and a `Shadow`, sliding left and right;
- a `Group` of two discs whose `opacity` pulses (a translucent group, composed apart and blended at every frame);
- a progress `Arc` with a gradient and round caps, sweeping around a static track;
- a `Text` with the frame number.

Below them, a full-screen `RadialGradient`: it never changes, so Slate copies it from the underlay under the moving
parts instead of computing it again.

Besides the timer:

- **IMU:** the QMI8658 is polled at 100 Hz (`$imu->poll(hz: 100, ..., every: 5)`); 20 times a second the newest
  sample moves a bubble with the tilt of the board.
- **Touch:** `onTouch()` of the scene moves a circle under the finger, and hides it when the finger is lifted.
- **Wi-Fi:** `wifi_connect(..., wait: false)` starts the connection and returns at once; the scene does not wait for
  it. Every 2 seconds a line of text shows the state (`wifi: connecting`, then the IP address and the signal).

## Configure Wi-Fi

The credentials come from a `.env` file next to `composer.json` (`"env": ".env"` in `extra.baremetal`): it is read
at build time and baked into the firmware, where the script reads it as `$_ENV`. Keep it out of version control.

```sh
cp .env.example .env    # then set WIFI_SSID and WIFI_PASSWORD (empty for an open network)
```

Without a `.env`, or with an empty `WIFI_SSID`, the scene runs the same without Wi-Fi and the text reads
`wifi: no .env`. If the access point is out of range the board keeps retrying in the background, which is part of
the load.

## Run it

```sh
composer install
phpbm flash
phpbm monitor
```

## What fps to expect, and why

Measured on this board (ESP32-S3 at 240 MHz, 32 KB instruction cache), 60 s per run:

| Wi-Fi | Frames per second (mean) | Compose (median) |
| --- | --- | --- |
| connected | 55 | 13 ms |
| access point out of range, retrying | 55–56 | 13 ms |

A console line looks like this one:

```text
slate: 59.5 fps | frame 15.3 ms (compose 11.4, wait 3.8) | dirty 14% in 8 rects | 24 elements, 23 drawn, 0 culled | cache 1 hit 0 miss | cmds 1744
```

- **Why not always 60:** a frame redraws about 15 % of the screen in about ten small regions, and the drawing of
  the moving elements themselves (translucent dots, the opacity group, the gradient card) takes about one refresh
  period (16.7 ms) of compositor time. The compositor shares the instruction cache and the external RAM with the
  PHP interpreter on the other core, which is busy with the 60 Hz listener and the IMU, so some frames miss a
  refresh and the next one carries both steps. The panel never shows a half-drawn frame because of it.
- **Seconds below 50:** the second in which Wi-Fi completes the association can drop below 30 fps, and heavy
  network traffic on the same core costs a few frames per second (about 50 fps measured with continuous HTTP
  downloads). A listener that blocks PHP (a blocking network call, for example) stops the changes, not the
  compositor: the frames already handed over are still drawn.
- **Over budget lines:** `slate: frame ... ms over budget in Stress` appears now and then: it is a frame longer than
  the panel's period, the case above.
- Hold the board still and keep your finger off the screen: the timer keeps the scene moving, so the profiler keeps
  counting frames. A scene that does not change composes nothing (`0.0 fps`): see `slate-hello`.

## Other boards

`composer.json` targets `waveshare-esp32-s3-touch-lcd-1.85b`: the IMU is on the board's I2C bus (SDA 11, SCL 10), and
`Slate::onboard()` takes the panel and the touch screen from the board's `display` and `touch` blocks. The positions
in the scene are written for a 360 x 360 panel.
