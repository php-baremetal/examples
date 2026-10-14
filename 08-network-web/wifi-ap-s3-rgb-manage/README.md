# wifi-ap-s3-rgb-manage

Control the onboard RGB LED of an ESP32-S3 board from a phone or a PC, live. The board starts its own Wi-Fi
access point and serves a control page; the page talks to the board over a WebSocket, so the LED follows the
sliders as you drag them, and every open browser stays in sync.

There is no router and no internet involved: you join the board's network and open its address.

## What it shows

- `wifi_ap_start()`: the board is the access point.
- The `event-driven` runtime: `init.php` runs once, then the LED state is a plain PHP variable that lives
  between events, with no store or session.
- `serve_http()` and `serve_ws()` on the same server: a `Request` listener for the page, a `Message` listener
  for the WebSocket frames, and `ws_broadcast()` to push the state to every client.
- `every()`: a board-side timer that animates the LED with no browser open.
- `led-ws2812`: the onboard WS2812, a single pixel in RGB order, set in HSV.

## Boards

`composer.json` targets `waveshare-esp32-s3-zero-n8r8` (8 MB flash, 8 MB PSRAM). Any ESP32-S3 board with an
onboard WS2812 works: set its id in `extra.baremetal.board` and its LED pin in `LED_PIN`.

On a board with 2 MB of PSRAM (for example `waveshare-esp32-s3-zero` with an ESP32-S3FH4R2), it runs, but
little PSRAM is left: once a phone joins the access point, the Wi-Fi buffers take almost all of it. Use
esp32-web-hal 1.0.2 or later there. Earlier versions answered every request with `503 Service Unavailable`.

## Run it

```sh
composer install
phpbm flash
```

The console prints the address once the access point is up:

```text
RGB control up: join 'php-rgb' (pw 'baremetal') and open http://192.168.4.1/
```

1. Join the Wi-Fi network `php-rgb`, password `baremetal`.
2. Open `http://192.168.4.1/`. Type the `http://`, because the board does not serve HTTPS.
3. Drag the sliders.

A phone may keep using mobile data because the network has no internet. If the page does not load, turn
mobile data off or tell the phone to stay connected.

## The page

| Tab | What it does |
| --- | --- |
| Colour | Hue, saturation and brightness sliders and an on/off button. While you drag, the page sends at most one frame every 40 ms, then the final value. |
| Effects | Effects that run on the board: disco, rainbow, pulse, strobe. A timer on the board animates the LED every 120 ms and broadcasts each frame, so they keep going with every browser closed. |
| JS FX | Effects computed in the browser (the same four plus candle), streamed to the board as colour frames every 120 ms. They stop when the tab closes. |

Moving a slider or pressing a button stops any running effect.

## Routes and messages

| Route | Answer |
| --- | --- |
| `GET /` | The control page, rendered with the current colour (`index.php` and `page.php`). |
| `GET /app.js` | The page's script. |
| `WS /ws` | The live channel. |

Frames are JSON in both directions. The page sends a colour, an effect, or both:

```json
{"h": 210, "s": 255, "v": 40, "on": 1}
{"effect": "rainbow"}
```

`h` is 0 to 359, `s` and `v` are 0 to 255, `on` is 0 or 1; values out of range are clamped. `effect` is one
of `none`, `disco`, `rainbow`, `pulse` or `strobe`; anything else counts as `none`. A colour without
`effect` stops the running effect.

After each change the board broadcasts the whole state to every client:

```json
{"h": 210, "s": 255, "v": 40, "on": 1, "effect": "none"}
```

## Files

| File | Role |
| --- | --- |
| `project-src/init.php` | The entry script: access point, LED, listeners, the effect timer, the server. |
| `project-src/index.php` | The page controller: passes the current colour to the template. |
| `project-src/page.php` | The HTML template, server-rendered with the current slider positions. |
| `project-src/app.js` | The browser side: WebSocket, sliders, tabs and the JS effects. |

## Settings

At the top of `init.php`:

| Constant | Default | Meaning |
| --- | --- | --- |
| `LED_PIN` | `48` | Data pin of the onboard WS2812 (48 on most ESP32-S3 boards, 38 on some). |
| `AP_SSID` | `php-rgb` | Name of the access point. |
| `AP_PASS` | `baremetal` | WPA2 password, at least 8 characters; `''` for an open network. |

The LED starts as a very dim blue (`h` 210, `v` 1).

## Troubleshooting

| Symptom | Cause |
| --- | --- |
| Connection refused | The browser tried HTTPS, or the phone is not on `php-rgb`. |
| `503 Service Unavailable` on a 2 MB PSRAM board | esp32-web-hal older than 1.0.2: the request had no memory left. The console prints `no memory for the request` from 1.0.2. |
| The LED does not light | Wrong `LED_PIN` for the board, or a board without a WS2812. |
| The page says "disconnected, retrying" | The board restarted, or the phone left the network; the page reconnects every second. |
