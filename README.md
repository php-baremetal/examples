# php-baremetal examples

Example projects for **php-baremetal** — real PHP running on microcontrollers. Each folder is a
self-contained [`phpflash`](https://github.com/php-baremetal/php-esp32) project: a
`php-baremetal.config.toml` that selects the board, the storage mode and any optional extensions, plus a
`project-src/` folder with the PHP code.

## Prerequisites

These examples build with `phpflash`, which comes with the engine repository. Install it first:

- **php-baremetal / php-esp32** (the engine + `phpflash`): see
  <https://github.com/php-baremetal/php-esp32> and run `phpflash system-setup` once.

`phpflash` finds the engine checkout via the config, the `PHP_ESP32_DIR` environment variable, or its
default. The two starter examples (`hello` and a `blink` sketch) live in the engine repo; everything
else lives here.

## Running an example

```sh
cd 01-language/language-tour
phpflash build      # build the firmware with the extensions the config declares
phpflash flash      # flash it to the board
phpflash monitor    # watch the serial output
```

To run from a microSD instead of flashing the source in, copy the contents of `project-src/` to the
card root (so its `index.php` sits at `/index.php`), insert the card and reset the board.

Each example's config targets a specific board by `preset`; change `[esp32] preset` (or `family`) to
build for a different one. `phpflash discover` lists the presets your checkout can build.

## The examples

### 01 · Language & engine
`language-tour` · `require-demo` · `env-demo` · `composer-collections` · `custom-partitions` ·
`benchmark` · `patch-test` · `ext-selftest`

### 02 · Extensions
`ctype` · `date` · `filter` · `mbstring` · `openssl-compat` · `openssl-full` · `session` · `sqlite` ·
`tokenizer`

### 03 · Storage & state
`sd-write` · `store-demo`

### 04 · GPIO & LED
`blink-sos` · `button-led` · `s3-rgb-show`

### 05 · I²C & sensors
`i2c-scan` · `i2c-core1` · `imu-events` · `imu-ws-stream` · `mmc5603` · `rtc-pcf85063` ·
`compass` · `rtc-clock`

### 06 · Display
`display-hello` · `oled-ssd1306-fps` · `oled-ssd1306-ext`

### 07 · Event-driven
`event-driven-hello` · `events-hello` · `http-events` · `gpio-interrupt`

### 08 · Network & web
`web-server` · `web-server-init-loop` · `web-init-mem` · `https-client` · `wifi-connect` · `wifi-ap` ·
`wifi-ap-s3-rgb-manage` · `json-api` · `sensor-webpage`

### 09 · Frameworks
`eloquent` · `laravel-demo` · `laravel-demo-optimized` · `symfony-demo`

### 10 · Power
`power-save` · `power-bench`

### 11 · Security
`secure-boot`

## Composer examples

`composer-collections`, `eloquent` and the framework examples need a `vendor/` tree generated with
`composer install` inside `project-src/` (it is gitignored, not committed). Each one's README walks
through it. The firmware needs FAT long filenames, already enabled in the default `sdkconfig`.

## License

MIT — see [LICENSE](LICENSE).
