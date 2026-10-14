<?php
// A serial compass from the MMC5603 magnetometer: the heading in degrees plus the nearest cardinal
// point, updated a few times a second. Hold the board flat and turn it to watch the heading track.

use Baremetal\I2c\Bus;
use Baremetal\I2c\Driver\Mmc5603;

$mag = new Mmc5603(new Bus(sda: 7, scl: 8));

function cardinal(float $deg): string
{
    $deg = fmod($deg + 360.0, 360.0);
    return ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'][(int) round($deg / 45) % 8];
}

function loop(): void
{
    global $mag;

    [$x, $y, $z] = $mag->mag();
    $h = $mag->heading();
    printf("heading %5.1f deg  %-2s   field uT [% 6.1f % 6.1f % 6.1f]\n",
        $h, cardinal($h), $x, $y, $z);
    usleep(300000);
}
