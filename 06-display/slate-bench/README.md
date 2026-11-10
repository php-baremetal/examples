# slate-bench

What a whole new screen costs with Slate: thirteen full-screen scenes, each with one kind of element or effect,
shown one after the other every 2 seconds, with the Slate profiler on.

## What it measures

Every scene fills the 360 x 360 panel, so its first frame composes and sends the whole screen: the time of that
frame is what a page change to such a screen costs. Then the scene stands still and Slate composes nothing until
the next one.

| Case | What fills the screen |
| --- | --- |
| `opaque-a`, `opaque-b` | One opaque `RoundRect` (two colours, so that the second is a real change) |
| `alpha` | A `RoundRect` at half alpha over an opaque one |
| `linear` | A `RoundRect` with a two-stop `LinearGradient` |
| `radial` | A `Circle` of radius 180 with a `RadialGradient` |
| `circle` | A `Circle` of radius 170 |
| `rounded` | A `RoundRect` 320 x 320 with radius 40 |
| `text` | 16 lines of `Text` |
| `arc` | An `Arc` of radius 150, width 20 |
| `shadow` | A 240 x 240 `RoundRect` with `Shadow(0, 6, 14, 0xA0000000)` (the shadow is built at its first frame) |
| `group-opacity` | A full-screen `Group` with `opacity: 0.6` |
| `group-blur` | A 240 x 240 `Group` with `blur: 8` |
| `group-shadow` | A 240 x 240 `Group` with a `Shadow` |

From `radial` on, each case is drawn over an opaque dark background. The list runs twice, then the last scene
stays on the screen.

## Run it

```sh
composer install
phpbm flash
phpbm monitor
```

## How to read the lines

When a scene is shown the script prints `BENCH <name>`, and the profiler prints its line once a second:

```text
BENCH alpha
slate: 1.0 fps | frame 14.5 ms (compose 13.2, wait 1.3) | dirty 100% in 1 rects | 7 elements, 20 drawn, 0 culled | cache 0 hit 0 miss | cmds 28
slate: 0.0 fps | frame 0.0 ms (compose 0.0, wait 0.0) | dirty 0% in 0 rects | 7 elements, 0 drawn, 0 culled | cache 0 hit 0 miss | cmds 0
```

- The line with `dirty 100%` is the scene's full-screen frame: `frame` is its time, split into `compose` (drawing
  the bands) and `wait` (waiting for the panel to take them).
- The `0.0 fps` line is the second in which the scene stands still: no frame.
- The profiler's line comes once a second and a scene lasts two, so a scene's line can appear just after the next
  `BENCH` line: take the `dirty 100%` line between two `BENCH` lines, or right after.
- A frame longer than the panel's period (16.7 ms) also prints `slate: frame ... ms over budget in Bench`.

## Reference numbers

Measured on the Waveshare ESP32-S3-Touch-LCD-1.85B (ESP32-S3 at 240 MHz with a 32 KB instruction cache, PSRAM,
ST77916 360 x 360 panel on QSPI), both passes of the list:

| Case | Frame (ms) | Compose (ms) |
| --- | --- | --- |
| `opaque-a` | 12.6 | 4.7 |
| `opaque-b` | 12.3–12.5 | 4.5–4.6 |
| `alpha` | 14.5 | 13.2 |
| `linear` | 12.8–12.9 | 5.5 |
| `radial` | 42.4 | 42.2 |
| `circle` | 13.2 | 7.2 |
| `rounded` | 12.9 | 5.9 |
| `text` | 13.2–13.4 | 10.2–10.3 |
| `arc` | 13.4–13.5 | 10.1–10.3 |
| `shadow` | 31.5 | 28.8 |
| `group-opacity` | 50.2–50.3 | 50.1 |
| `group-blur` | 135.3–135.6 | 135.1–135.4 |
| `group-shadow` | 122.6–122.9 | 122.5–122.7 |

- About 12.5 ms is the floor: the time to send a whole screen to the panel. Below it, the drawing hides behind the
  transfer (a high `wait`); above it, the drawing is the limit.
- The radial gradient costs most of the shapes: its rows are cut into short pieces, each with its own square roots.
- `shadow`, `group-blur` and `group-shadow` build their image at the first frame; moving the element later reuses
  it. Their times depend on where the images land in external RAM: `group-shadow` has measured anywhere between
  about 70 and 125 ms from one firmware build to the next.
- A full-screen group with `opacity` below 1 is composed apart and then blended: keep translucent groups small.

The `Performance` page of the ext-slate documentation explains what each of these costs comes from.
