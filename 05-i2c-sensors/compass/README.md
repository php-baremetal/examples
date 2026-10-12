# compass

Turns the MMC5603 3-axis magnetometer into a serial compass: it reads the magnetic field, computes a
heading with `$mag->heading()`, and prints the degrees plus the nearest cardinal point (N, NE, E, …).

Uses the `i2c` extension with the `mmc5603` driver (`Baremetal\I2c\Driver\Mmc5603`). The I²C pins are
passed at construction (`new Bus(sda: 7, scl: 8)`) — change them to match your wiring.

> A magnetometer needs to be away from motors and large metal, and ideally calibrated (hard/soft-iron)
> for an accurate absolute heading. Raw readings still track rotation well enough to see it work.

## Run

```sh
phpflash build && phpflash flash && phpflash monitor
```
