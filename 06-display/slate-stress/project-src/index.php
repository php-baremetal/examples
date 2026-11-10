<?php
/**
 * slate-stress: everything moves at once, with the slate-debug profiler on.
 *
 * - 12 translucent dots orbit at different radii and speeds, a gradient card with a drop shadow slides left and
 *   right, a group of two discs pulses its opacity, a progress ring sweeps and a counter shows the frame number:
 *   all of them set by one 60 Hz TimerEvent.
 * - The QMI8658 IMU is polled at 100 Hz and moves a bubble with the tilt; a finger on the screen moves a circle.
 * - Wi-Fi connects in the background with the credentials of .env (copy .env.example to .env); without them the
 *   scene runs the same, offline.
 * - "slate-debug": true in composer.json draws the fps counter and prints a line of timings per second.
 */
use Baremetal\Events;
use Baremetal\TimerEvent;
use Baremetal\I2c\Bus;
use Baremetal\I2c\Driver\Qmi8658;
use Baremetal\Sensor\Imu\SamplesReady;
use Baremetal\Input\{Released, TouchEvent};
use Baremetal\Slate\{Align, Arc, Caps, Circle, Group, LinearGradient, RadialGradient, RoundRect, Scene, Shadow, Slate, Text};

final class Frame extends TimerEvent {}
final class Slow extends TimerEvent {}

final class Stress extends Scene
{
    /** @var Circle[] */ public array $dots = [];
    public RoundRect $card; public Group $pulse; public Arc $ring; public Text $count; public Text $net;
    public Circle $bubble; public Circle $finger;

    protected function build(): void
    {
        $this->add(new RoundRect(0, 0, 360, 360, fill: new RadialGradient([[0.0, 0x1A2040], [1.0, 0x05060C]])));
        $this->add(new Arc(180, 180, 150, 0, 360, 6, 0x1C2238));
        $this->add($this->ring = new Arc(180, 180, 150, -90, -90, 6, new LinearGradient([[0.0, 0x40E0A0], [1.0, 0x6080FF]]), Caps::Round));
        $colors = [0xFF5050, 0xFFA040, 0xFFE040, 0x60E060, 0x40C0FF, 0x8060FF, 0xFF60C0, 0xFFFFFF];
        for ($i = 0; $i < 12; $i++) {
            $this->dots[] = new Circle(180, 180, 5 + $i % 4, fill: 0xC0000000 + $colors[$i % 8]);
        }
        $this->add(...$this->dots);
        $this->card = new RoundRect(60, 150, 110, 56, radius: 14,
            fill: new LinearGradient([[0.0, 0x2A3A66], [1.0, 0x8A4CD0]], angle: 0), shadow: new Shadow(0, 6, 12, 0xA0000000));
        $this->add($this->card);
        $this->pulse = new Group(180, 250, opacity: 1.0);
        $this->pulse->add(new Circle(-14, 0, 18, fill: 0xE04040), new Circle(14, 0, 18, fill: 0x4080F0));
        $this->add($this->pulse);
        $this->add($this->count = new Text(180, 96, 'frame 0', color: 0xC0D0F0, align: Align::Center));
        $this->add($this->net = new Text(180, 120, 'wifi: ...', color: 0x8090B0, align: Align::Center));
        $this->add($this->bubble = new Circle(180, 180, 10, fill: 0xA0FFFFFF, border: 2, borderColor: 0x40E0A0));
        $this->add($this->finger = new Circle(-50, -50, 22, fill: 0x6040E0A0));
    }

    public function onTouch(TouchEvent $e): void
    {
        if ($e instanceof Released) { $this->finger->set(x: -50, y: -50); return; }
        $this->finger->set(x: $e->x, y: $e->y);
    }
}

$slate = Slate::onboard();
$s = new Stress();
$slate->show($s);

$t = 0;
Events::listen(Frame::class, function (Frame $e) use ($s, &$t): void {
    $t += $e->ticks;
    $f = $t / 60.0;
    foreach ($s->dots as $i => $d) {
        $r = 40 + $i * 9; $w = (0.6 + 0.15 * $i) * ($i % 2 ? 1 : -1);
        $d->set(x: 180 + $r * cos($f * $w + $i), y: 180 + $r * sin($f * $w + $i));
    }
    $s->card->x = 125 + 65 * sin($f * 1.3) - 55;
    $s->pulse->opacity = 0.55 + 0.45 * sin($f * 2.0);
    $s->ring->to = -90 + fmod($f * 90, 360);
    $s->count->text = "frame $t";
});
every(1000 / 60, Frame::class);

$imu = new Qmi8658(new Bus(sda: 11, scl: 10));
Events::listen(SamplesReady::class, function (SamplesReady $e) use ($s): void {
    $last = null;
    foreach ($e->device->drain() as $raw) { $last = $raw; }
    if ($last === null) { return; }
    ['accel' => [$ax, $ay]] = $e->device->decode($last);
    $s->bubble->set(x: 180 + max(-1, min(1, $ax)) * 60, y: 180 + max(-1, min(1, $ay)) * 60);
});
$imu->poll(hz: 100, depth: 64, event: SamplesReady::class, every: 5);

$ssid = $_ENV['WIFI_SSID'] ?? '';
if ($ssid !== '') {
    wifi_connect($ssid, ($_ENV['WIFI_PASSWORD'] ?? '') ?: null, wait: false);
}
Events::listen(Slow::class, function () use ($s, $ssid): void {
    $s->net->text = match (true) {
        $ssid === '' => 'wifi: no .env',
        wifi_connected() => 'wifi ' . wifi_ip() . ' ' . wifi_rssi() . ' dBm',
        default => 'wifi: connecting',
    };
});
every(2000, Slow::class);
