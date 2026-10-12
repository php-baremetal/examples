# sensor-webpage

A small real device in one script: the board brings up its own **WiFi access point**, runs an
**HTTP source** on the reactor, and on each request reads the **MMC5603 magnetometer** and returns the
current heading. `GET /` serves a self-refreshing HTML page; `GET /heading` returns JSON.

It combines three event sources — WiFi (`wifi_ap_start`), HTTP (`serve_http` + `Baremetal\Http\Request`/
`Response`) and a sensor (`Baremetal\I2c\Driver\Mmc5603`) — which is roughly the shape of a real
project. Uses the `wifi`, `web` and `i2c` extensions.

## Run

```sh
phpflash build && phpflash flash && phpflash monitor
```

Join the `php-esp32` access point (password `baremetal`), then open the printed `http://<ap-ip>/`.
