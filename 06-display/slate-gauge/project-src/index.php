<?php
/**
 * slate-gauge: a gauge driven by the tilt of the QMI8658 IMU, with the slate-debug profiler on.
 *
 * - The IMU is polled on core 1 at 100 Hz; SamplesReady arrives every 10th sample (10 events a second). The
 *   listener takes the newest sample, smooths the left-right tilt and sets the gauge.
 * - A dot orbits the panel's edge at 60 Hz (a TimerEvent): a constant motion, so the frames per second shown by the
 *   profiler are those of a scene that really changes at the panel's pace. When nothing moves the profiler reads
 *   0.0 fps: a still screen is no frame.
 * - "slate-debug": true in composer.json draws the fps counter and prints a line of timings per second.
 */

use Baremetal\Events;
use Baremetal\I2c\Bus;
use Baremetal\I2c\Driver\Qmi8658;
use Baremetal\Sensor\Imu\SamplesReady;
use Baremetal\Slate\{Align, Arc, Circle, Group, LinearGradient, RoundRect, Slate, Scene, Text};
use Baremetal\TimerEvent;

final class Orbit extends TimerEvent {}

/** A component of your own: a Group with its parts and one method to move it. */
final class Gauge extends Group
{
    private Arc $bar;
    private Text $label;

    public function __construct(int $x, int $y)
    {
        parent::__construct($x, $y);
        $this->add(
            new Arc(0, 0, 90, -225, 45, 16, 0x26304C),
            $this->bar = new Arc(0, 0, 90, -225, -225, 16, new LinearGradient([[0.0, 0x40E0A0], [1.0, 0xE0A040]])),
            $this->label = new Text(0, -8, '50 %', align: Align::Center),
            new Text(0, 40, 'tilt', color: 0x8090B0, align: Align::Center),
        );
    }

    /** $v from 0.0 to 1.0. */
    public function setValue(float $v): void
    {
        $this->bar->to = -225 + 270 * $v;
        $this->label->text = sprintf('%d %%', round($v * 100));
    }
}

final class Dial extends Scene
{
    public Gauge $gauge;
    public Circle $dot;

    protected function build(): void
    {
        $this->add(
            new RoundRect(0, 0, 360, 360, fill: 0x0B1020),
            $this->gauge = new Gauge(180, 190),
            $this->dot = new Circle(180, 14, 7, fill: 0xFFFFFF),
        );
    }
}

$slate = Slate::onboard();
$dial  = new Dial();
$slate->show($dial);
$dial->gauge->setValue(0.5);

$imu = new Qmi8658(new Bus(sda: 11, scl: 10));

$value = 0.5;
Events::listen(SamplesReady::class, function (SamplesReady $e) use ($dial, &$value): void {
    $last = null;
    foreach ($e->device->drain() as $raw) {
        $last = $raw;
    }
    if ($last === null) {
        return;
    }
    ['accel' => [$ax]] = $e->device->decode($last);                  // tilt left-right, in g
    $target = max(0.0, min(1.0, ($ax + 1.0) / 2.0));                 // -1 g .. +1 g -> 0 .. 1
    $value += ($target - $value) * 0.3;                              // a little smoothing
    $dial->gauge->setValue($value);
});
$imu->poll(hz: 100, depth: 64, event: SamplesReady::class, every: 10);

$angle = 0.0;
Events::listen(Orbit::class, function (Orbit $e) use ($dial, &$angle): void {
    $angle = fmod($angle + 0.04 * $e->ticks, 2 * M_PI);              // about 0.4 turns a second at 60 Hz
    $dial->dot->set(x: 180 + 166 * sin($angle), y: 180 - 166 * cos($angle));
});
every(1000 / 60, Orbit::class);

echo "slate-gauge: tilt the board left and right; the dot orbits at 60 Hz; read the slate: lines\n";
