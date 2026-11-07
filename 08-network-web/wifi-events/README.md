# wifi-events

Follows the Wi-Fi station with events, in the `event-driven` runtime. The script starts a scan without
blocking, then returns; when the scan is done it starts the connect, also without blocking. The reactor prints
what the radio does as it happens.

## What it shows

- `wifi_events()` turns the Wi-Fi events on; listeners print each `StateChanged` (`previous -> state (ssid)`),
  the networks of `ScanDone` with their signal, `Online` with the IP address, `ConnectFailed` and
  `Disconnected` with the reason and the driver's code (`retrying` when the driver reconnects by itself).
- `wifi_scan(wait: false)` and `wifi_connect(..., wait: false, retries: -1)` start the work and return at once.
  The connect starts from the `ScanDone` listener, because the driver refuses a scan while the station joins
  and may refuse a connect while a scan runs. Both return values are checked: a start the driver refuses
  returns `false` and raises no event, so the script prints `the scan did not start` or
  `the connect did not start`.
- With `retries: -1` the station keeps trying after every failure or drop, except two password refusals in a
  row, which give up at once.
- An `every(5000)` timer prints `wifi_state()` and `wifi_rssi()`.

## Run it

Copy `.env.example` to `.env`, set `WIFI_SSID` and `WIFI_PASSWORD` (leave the password empty for an open
network), then build and flash the project as usual with `phpbm`. The `.env` is baked into the firmware at build
time and stays out of git; the password is never printed. Without an SSID the example only scans.

## Things to try

- Switch the hotspot off and on again: you see `Disconnected` (`lost`, retrying), the state going back to
  `connecting` and, once the network is back, `Online`.
- Set a wrong password: `ConnectFailed` with `wrong-password` after the second refusal, a few seconds, even
  with `retries: -1`, and the state goes back to `idle`. A single refusal is retried, because a weak link can
  cause one with a correct password.
- Set an SSID that does not exist: with `retries: -1` the station keeps looking for it, so the state stays
  `connecting` and no event says why (there was no association, so no `Disconnected` either). Change the call
  to `retries: 3` for this experiment: after the retries, `ConnectFailed` with `not-found`.

This is the base of a Wi-Fi screen: a status line driven by `StateChanged`, a network list filled by
`ScanDone`, a message for each failure reason. See the ext-wifi `usage/events.md` for every event and reason.
