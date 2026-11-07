<?php
/**
 * The executor feeding the reactor: core 1 polls the IMU, core 0 reacts.
 *
 * $imu->poll(hz, depth, event: SamplesReady::class, every: 10) hands the sampling to the core-1 executor AND asks
 * it to emit a SamplesReady event (every 10th sample here, see below). The event travels to the reactor on core 0, which
 * delivers it as a typed object -- $e->device is the IMU that produced it. The handler reads the ring
 * (drain(), no bus traffic) and decodes; between events the reactor sleeps.
 *
 * The IMU runs at 100 Hz and `every: 10` asks for one event per 10 samples, so the reactor wakes 10 times a
 * second instead of 100. $e->count is the number of unread samples in the ring when the event is delivered.
 *
 * SamplesReady is the shared sensor contract: the same handler works for any polled IMU, whatever chip.
 */

use Baremetal\Events;
use Baremetal\I2c\Bus;
use Baremetal\I2c\Driver\Qmi8658;
use Baremetal\Sensor\Imu\SamplesReady;

$imu = new Qmi8658(new Bus(sda: 11, scl: 10));

$seen = 0;

Events::listen(SamplesReady::class, function (SamplesReady $e) use (&$seen): void {
    printf("samples-ready: %d unread\n", $e->count);
    foreach ($e->device->drain() as $raw) {          // every sample since the last event, from the ring
        ['accel' => [$ax, $ay, $az]] = $e->device->decode($raw);
        printf("  #%d  accel = [% .2f % .2f % .2f] g\n", ++$seen, $ax, $ay, $az);
    }
});

$imu->poll(hz: 100, depth: 64, event: SamplesReady::class, every: 10);   // core-1 poll at 100 Hz, one event per 10 samples

echo "imu-events: polling on core 1, reacting to SamplesReady on core 0\n";
