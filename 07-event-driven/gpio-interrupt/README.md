# gpio-interrupt

The **event-driven** model wired to hardware: `watch_gpio(0, Pressed::class)` turns the BOOT button
(GPIO0) into a debounced interrupt source. Each press arrives at the reactor as a `Pressed` event, and
the listener toggles an LED on GPIO2. There is no `loop()` and no polling — the CPU sleeps until the
button is pressed, which is the whole point of the event-driven model for low-power, reactive devices.

`gpio_*` is built in (no extension to enable). Wire an LED + ~330 Ω between GPIO2 and GND; the BOOT
button is already on GPIO0 on most dev boards.

## Run

```sh
phpflash build && phpflash flash && phpflash monitor
```
