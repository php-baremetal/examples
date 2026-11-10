<?php
/**
 * Slate bench: full-screen scenes shown one after the other, every 2 seconds, twice.
 *
 * Each scene fills the whole 360 x 360 panel with one kind of element or effect, so its first frame composes and
 * sends the whole screen. With the slate-debug option on, the console prints a "BENCH <name>" line when a scene
 * is shown and a "slate:" line every second: the frame time of that scene is in the line with "dirty 100%".
 */
use Baremetal\Events;
use Baremetal\TimerEvent;
use Baremetal\Slate\{Align, Arc, Circle, Group, LinearGradient, RadialGradient, RoundRect, Scene, Shadow, Slate, Text};

final class Next extends TimerEvent {}

/** One bench case: a name and a closure that adds its elements to the scene. */
final class Bench extends Scene
{
    public function __construct(public string $name, private \Closure $f) { }
    protected function build(): void { ($this->f)($this); }
}

$cases = [
  new Bench('opaque-a', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x203040))),
  new Bench('opaque-b', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x402030))),
  new Bench('alpha', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x203040), new RoundRect(0, 0, 360, 360, fill: 0x80FF8040))),
  new Bench('linear', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: new LinearGradient([[0.0, 0x2A3A66], [1.0, 0x5B3C8C]], angle: 90)))),
  new Bench('radial', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x101010), new Circle(180, 180, 180, fill: new RadialGradient([[0.0, 0xFFE9A0], [1.0, 0xE06020]])))),
  new Bench('circle', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x101010), new Circle(180, 180, 170, fill: 0x4080F0))),
  new Bench('rounded', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x101010), new RoundRect(20, 20, 320, 320, radius: 40, fill: 0x40E0A0))),
  new Bench('text', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x101010), ...array_map(fn($i) => new Text(180, 20 + $i * 20, 'The quick brown fox jumps', align: Align::Center), range(0, 15)))),
  new Bench('arc', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x101010), new Arc(180, 180, 150, 0, 360, 20, 0xA070E0))),
  new Bench('shadow', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x101010), new RoundRect(60, 60, 240, 240, radius: 20, fill: 0x3050A0, shadow: new Shadow(0, 6, 14, 0xA0000000)))),
  new Bench('group-opacity', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x101010), (new Group(opacity: 0.6))->add(new RoundRect(0, 0, 360, 360, fill: 0x2050C0), new Circle(180, 180, 150, fill: 0xF0A040)))),
  new Bench('group-blur', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x101010), (new Group(60, 60, blur: 8))->add(new RoundRect(0, 0, 240, 240, radius: 30, fill: 0x40A0E0), new Text(120, 110, 'Blurred', align: Align::Center)))),
  new Bench('group-shadow', fn($s) => $s->add(new RoundRect(0, 0, 360, 360, fill: 0x101010), (new Group(60, 60, shadow: new Shadow(0, 6, 14, 0xA0000000)))->add(new Circle(120, 120, 110, fill: 0xE04060), new Text(120, 110, 'Shadow', align: Align::Center)))),
];

$slate = Slate::onboard();
$i = 0;
$slate->show($cases[0]);
echo "BENCH ", $cases[0]->name, "\n";
Events::listen(Next::class, function () use ($slate, $cases, &$i): void {
    $i++;
    if ($i === count($cases) * 2) { echo "BENCH done\n"; }
    if ($i >= count($cases) * 2) { return; }
    $c = $cases[$i % count($cases)];
    echo "BENCH ", $c->name, "\n";
    $slate->show($c);
});
every(2000, Next::class);
