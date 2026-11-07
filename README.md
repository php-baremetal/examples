# php-baremetal examples

Example projects for **php-baremetal** — real PHP running on microcontrollers.

> **Version notice:** these examples target **SDK 2.0.0 and later** (`"sdk": "esp32@^2.0"`).
> The examples for earlier versions (the old `php-baremetal.config.toml` / `phpflash` model) live in the
> [`legacy`](https://github.com/php-baremetal/examples/tree/legacy) branch.

Each folder is a self-contained project described by its `composer.json`: the packages it needs under
`require`, and the target under `extra.baremetal` (`sdk`, `board`, `runtime`, `storage`). The PHP code lives
in `project-src/`.

## Running an example

Requires `phpbm`, the SDK (`phpbm sdk:install`) and its toolchain (`phpbm toolchain:install`).

```sh
cd 01-language/language-tour
composer install    # resolves the packages from https://repository.php-baremetal.com
phpbm build         # build the firmware
phpbm flash         # flash it to the board (microsd storage: sources are copied to the card)
phpbm monitor       # watch the serial output
```

`phpbm discover` lists the connected boards. To build for a different board, change
`extra.baremetal.board` in the example's `composer.json`.

Examples with a nested app (`composer-collections`, `eloquent`, the framework demos) also need
`composer install --no-dev -o` inside `project-src/` before building.

## The examples

### 00 · Hello
`00-hello`

### 01 · Language & engine
`language-tour` · `require-demo` · `env-demo` · `composer-collections` · `benchmark` · `patch-test` ·
`ext-selftest`

### 02 · Extensions
`ctype` · `date` · `filter` · `mbstring` · `openssl-compat` · `session` · `sqlite` · `tokenizer`

### 03 · Storage & state
`sd-write` · `store-demo`

### 04 · GPIO & LED
`blink-sos` · `button-led` · `led-strip` · `s3-rgb-show`

### 05 · I²C & sensors
`i2c-scan` · `i2c-core1` · `imu-events` · `imu-ws-stream` · `mmc5603` · `rtc-pcf85063` · `compass` ·
`rtc-clock`

### 06 · Display
`display-hello` · `display-hello-event-driven` · `display-vsync` · `display-fonts` · `oled-ssd1306-fps` · `oled-ssd1306-ext`

### 07 · Event-driven
`event-driven-hello` · `events-hello` · `button-led-events` · `gpio-multi` · `timer-control` · `http-events` · `gpio-interrupt`

### 08 · Network & web
`web-server` · `web-server-init-loop` · `web-init-mem` · `wifi-connect` · `wifi-events` · `wifi-ap` ·
`wifi-ap-s3-rgb-manage` · `json-api` · `sensor-webpage`

### 09 · Frameworks
`eloquent` · `laravel-demo` · `laravel-demo-optimized` · `symfony-demo`

### 10 · Power
`power-save` · `power-bench`

### 12 · Extension checks
`12-extension-checks`

## License

MIT — see [LICENSE](LICENSE).
