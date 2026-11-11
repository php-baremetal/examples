# slate-images

Pictures with Slate on the round panel of the Waveshare ESP32-S3-Touch-LCD-1.85B: icons from the firmware's flash,
a background photo from the microSD card, a PNG copied to the card by hand and decoded on the board, and a second
page preloaded while the first one is shown. It needs a microSD card in the board's slot.

## What it shows

- Image assets, declared in `extra.baremetal.assets` of `composer.json`:

  | Folder | Storage | On the board |
  | --- | --- | --- |
  | `assets/ui` (icons, 72 x 72 with transparent corners and a soft shadow) | `embedded` | `/app/assets/ui/icons/<name>.sli` |
  | `assets/photos` (two 360 x 360 photos) | `microsd` | `/sdcard/assets/photos/<name>.sli` |

  At every `build`, `flash` and `sync`, phpbm converts the `.png` files of those folders to `.sli`, Slate's own
  format, and puts them on the board: the icons in the flash with the sources, the photos on the card (only the
  files that changed are copied). The code uses the paths on the board.
- `Image`: an element that draws a file. It is loaded in the background as soon as the element is made; the page
  appears at once and each picture shows up when it is in memory. The same file in two elements is loaded once.
- A PNG decoded on the board: `/sdcard/picture.png` is not an asset, it is a file you copy to the card yourself.
  The example centres it when it is loaded (`ImageLoaded`), or says what is missing (`ImageFailed`, with
  `ImageError::NotFound` when there is no such file).
- `Images::preload()`: the images of the second page are loaded while the first one is shown. The second page is
  built at its first `show()`, so its `Image` elements find their files in memory and the page appears complete
  in its first frame. `PreloadDone` says when they are ready.

## Run it

It needs phpbm 0.3 or later, which converts and copies the assets: an earlier phpbm does not know
`extra.baremetal.assets`.

```sh
composer install
phpbm flash
phpbm monitor
```

Put a FAT-formatted microSD card in the slot before `phpbm flash`: it writes the firmware and then copies the
photos to the card, and without a card it ends with "copying the microSD assets to the card failed". For the
hand-placed picture, copy a small PNG to the root folder of the card as `picture.png` with a card reader: 8 bits
per channel, not interlaced (16-bit and interlaced PNG files fail with `unsupported`), and up to about 300 x 200
pixels, which fits the middle of the screen and the memory left next to the photos (a large picture, such as a
phone screenshot, fails with `no-memory`). Without it, or when it fails, the example shows a hint in its place.

## What to look at

- The evening photo fills the screen, and the four icons sit on it with their round edges smooth and a soft
  shadow below them: their transparent and semi-transparent pixels are blended with the photo.
- `picture.png` appears in the middle, decoded on the board; a PNG with transparency shows the photo through it.
- Touch anywhere: the dunes page appears at once, photo and icon included, with no frame where they are missing.
  Touch again and the first page is back, also at once (its images stayed in memory).
- The console prints one line per image loaded, and `next page ready` when the preload is done.

## Times

Measured on this board (ESP32-S3 at 240 MHz, microSD card on SPI): a 360 x 360 photo as `.sli` loads in about
25 ms from flash and 170 ms from the card; the same photo as a PNG takes about 300 ms to decode from flash. Without
the preload, the second page would show without its photo for about 0.2 s. See the performance page of ext-slate
for the details.

## Other boards

`composer.json` targets `waveshare-esp32-s3-touch-lcd-1.85b`. A board without a microSD slot cannot take the
`microsd` assets: put `assets/photos` in `embedded` storage (and change the paths in `index.php` to
`/app/assets/photos/...`), as long as the photos fit in the flash. The positions in the example are written for a
360 x 360 panel.
