# display-vsync

A rectangle runs across the round ST77916 panel of the Waveshare ESP32-S3-Touch-LCD-1.85B in the
`event-driven` runtime, one step per refresh of the panel, and the console reports the frame rate and the
transfer time of each flush.

## What it shows

- `St77916::onboard(vsync: VSYNC)`: with `const VSYNC = true;` every flush starts at the next pulse of the
  panel's TE (tearing effect) pin, so the panel never shows half of an old frame. The TE pin comes from the
  `te` key of the board's `display` block (GPIO 18 on this board); without it `vsync: true` throws.
- `onVsync(Frame::class)`: the panel is the metronome. `Frame` extends `Baremetal\TimerEvent`; `$e->ticks` is
  more than 1 when the listener fell behind and pulses were merged.
- A 60 x 120 pixel rectangle moving at 240 pixels per second (by the time between two events). Each frame
  redraws and flushes only the band the rectangle touches. `flush()` returns before the transfer ends, so the
  listener is free while the panel reads the framebuffer.
- `onFlushed(Done::class)`: `Done` extends `Baremetal\Display\Flushed`. `$e->us` is the transfer time of the
  region in microseconds. Once a second the listener prints the frames per second and the mean `$us`.

## Run it

```sh
composer install
phpbm flash
```

## What to look at

- Dark blue background with a yellow rectangle sliding from side to side.
- The serial console prints once a second, for example `60.0 fps  transfer <n> us  vsync on`.
- With `VSYNC = false` the edge of the rectangle sometimes breaks into two pieces, because the transfer starts
  while the panel is half way through a scan. With `VSYNC = true` it does not, as long as the transfer of the
  band takes less than one frame (16.7 ms): the band here is a few thousand pixels, a few milliseconds.
- If you grow the rectangle until a flush takes longer than a frame, the frame rate drops below 60 and
  `$ticks` on `Frame` goes above 1.

## Other boards

The board needs a `display` block with a `te` key. Without a TE pin, build the driver without `vsync` and use
`every()` for the pacing, as in `06-display/display-hello-event-driven`.
