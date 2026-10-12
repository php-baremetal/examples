# json-api

A minimal JSON HTTP API in the **web-server** model: the firmware runs a C HTTP server and executes
`index.php` fresh for each request (shared-nothing, like PHP-FPM). The script routes on the request
method and path and returns `json_encode(...)`.

- `GET /` — service info (PHP version, routes)
- `GET /ping` — `{ "pong": true, "rand": ... }`
- `POST /echo` — echoes the posted JSON body back

## Run

```sh
phpflash build && phpflash flash && phpflash monitor
```

The board prints the address it came up on. Then:

```sh
curl http://<board-ip>/ping
curl -X POST -d '{"hi":1}' http://<board-ip>/echo
```

Needs a networked board (P4-ETH or S3-ETH).
