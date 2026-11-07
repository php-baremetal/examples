# display-fonts

Text on the round ST77916 panel of the Waveshare ESP32-S3-Touch-LCD-1.85B with fonts from all four sources
that `ext-display` supports.

| Font | Source | Where it is set |
| --- | --- | --- |
| `default` | Built into `ext-display` (DejaVu Sans Mono 16 px). | Nothing to do; used when `font:` is omitted. |
| `title` | Baked at build time from `fonts-src/DejaVuSans.ttf`, 28 px, 2 bits per pixel. | `extra.baremetal.fonts.bake` in `composer.json` |
| `clock-48` | A header in `project-fonts/`. | The file name is the font name. |
| `dejavu-sans-16` | The `php-baremetal/font-dejavu` package. | `composer require php-baremetal/font-dejavu` |

## Run it

```sh
composer install
phpbm flash
```

The TTF and its license are in `fonts-src/`, so the example builds as it is.

## What you see

- `Città 21°C` in the large `title` font.
- Below it a clock in the 48 px `clock-48` font, counting minutes and seconds from the start of the script; the minutes wrap at 60. Only the clock's
  rectangle is redrawn and flushed each second.
- Two lines in smaller fonts: `Temperatura àèìòù °` in `dejavu-sans-16` and `default: àèìòù °` in the default
  font. The accents and the degree sign show in every font.

## Notes

`title` bakes only the characters in its `chars` entry, which are the ones the script draws with it. To draw
other text with `title`, add the characters to `chars`. A source font can be replaced: see
`fonts-src/README.md`.

`project-fonts/clock-48.h` was made from DejaVu Sans Mono 2.37 (`ttf/DejaVuSansMono.ttf` in the
`dejavu-fonts-ttf-2.37` archive of the
[DejaVu release](https://github.com/dejavu-fonts/dejavu-fonts/releases/tag/version_2_37)) with the phpbm
baker. To regenerate it:

```sh
phpbm font:bake DejaVuSansMono.ttf --name=clock-48 --size=48 --bpp=2 --chars="0123456789:" --out=project-fonts
```

Fonts take flash whether or not they are used: leave out the `font-dejavu` package, or set
`"default": false` in `fonts`, when a project does not need them.
