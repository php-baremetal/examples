# slate-hello

Scenes with Slate on the round panel of the Waveshare ESP32-S3-Touch-LCD-1.85B: one page with every kind of
element and effect, and a second page reached by touching the screen.

## What it shows

- The `event-driven` runtime: `index.php` runs once. `Slate::onboard()` takes the panel and the touch controller
  from the board's `display` and `touch` blocks, and `$slate->show($home)` shows the first page. There is no
  `loop()` and no drawing code: Slate composes the scene and sleeps when nothing changes.
- A page is a class that extends `Scene`: `Home` builds its components in `build()`, and `onTouch()` of the page
  shown receives the touch events (`Pressed` here) and shows the other page with `$slate->show()`.
- Elements: `RoundRect`, `Circle`, `Arc`, `Line`, `Text` (with `Align::Center`), `Group` and `Canvas`.
- Effects: a `LinearGradient` fill with a `Shadow` on the card, a `RadialGradient` on a disc, a gradient fading to
  transparent on the second page, a group with `opacity: 0.6`, and a group with `blur: 4`.
- Arcs and lines with round caps (`Caps::Round`) and colours with alpha (`0xAARRGGBB`).
- A `Canvas` drawn once with its primitives (`rect`, `fillCircle`, `circle`, `line`, `text`) and sent to the
  screen with `dirty()`.

## Run it

```sh
composer install
phpbm flash
phpbm monitor
```

## What to look at

- The edges of the card, the ring, the discs and the lines are smooth (anti-aliased), at any position.
- The card has a soft shadow below it, and its fill goes from blue to violet from top to bottom.
- The disc on the left is lit from its upper left corner (radial gradient).
- In the translucent group the red and blue discs overlap, and the overlap shows one blend of the two, not a
  second layer of opacity: a group is blended as a whole. The group next to it, with its blur, is soft.
- The canvas on the right has hard pixels: it is yours to draw, with no smoothing.
- Touch anywhere: the second page appears, with a violet glow; touch again and the first page is back. The screen
  is still between touches: Slate draws nothing when nothing changes.

## Other boards

`composer.json` targets `waveshare-esp32-s3-touch-lcd-1.85b`. On a board without a `display` block,
`Slate::onboard()` throws `\LogicException`; build the Slate with `new Slate($display, $touch)` and a display
driver that has the band path (`esp32-display-st77916` 1.2 or later). The positions in the example are written
for a 360 x 360 panel.
