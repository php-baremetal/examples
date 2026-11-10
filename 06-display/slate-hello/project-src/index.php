<?php
/**
 * slate-hello: scenes with Slate on the round 360 x 360 panel. No loop(): Slate composes only what changes, and a
 * touch on the screen switches page.
 *
 * Home shows every kind of element and effect; Second is reached by touching the screen and goes back the same way.
 *
 * Slate::onboard() takes the panel and the touch controller from the board's `display` and `touch` blocks.
 */

use Baremetal\Input\Pressed;
use Baremetal\Input\TouchEvent;
use Baremetal\Slate\{Align, Arc, Canvas, Caps, Circle, Group, LinearGradient, Line, RadialGradient, RoundRect, Scene, Shadow, Slate, Text};

final class Home extends Scene
{
    protected function build(): void
    {
        // The compositor has no background of its own: an opaque full-screen rectangle is the cheapest one.
        $this->add(new RoundRect(0, 0, 360, 360, fill: 0x0B1020));

        $this->add(new Text(180, 20, 'Slate', color: 0x8090B0, align: Align::Center));

        // A card: gradient fill and a drop shadow.
        $this->add(
            new RoundRect(70, 46, 220, 84, radius: 18,
                fill: new LinearGradient([[0.0, 0x2A3A66], [1.0, 0x5B3C8C]], angle: 90),
                shadow: new Shadow(0, 6, 14, 0xA0000000)),
            new Text(180, 62, 'gradient + shadow', align: Align::Center),
            new Text(180, 90, 'touch: next page', color: 0xC0D0F0, align: Align::Center),
        );

        // A disc with a radial gradient, a progress ring with round caps and a canvas.
        $this->add(
            new Circle(100, 185, 34, fill: new RadialGradient([[0.0, 0xFFE9A0], [1.0, 0xE06020]], 0.35, 0.35, 0.7)),
            new Arc(180, 185, 30, 0, 360, 8, 0x26304C, Caps::Round),
            new Arc(180, 185, 30, -90, 160, 8, 0x40E0A0, Caps::Round),
            new Text(180, 177, '70%', align: Align::Center),
        );

        $canvas = new Canvas(230, 150, 70, 70);
        $canvas->clear(0x182030);
        $canvas->rect(0, 0, 70, 70, 0x5070A0);
        $canvas->fillCircle(22, 44, 14, 0xE0A040);
        $canvas->circle(48, 24, 14, 0x40E0A0);
        $canvas->line(4, 66, 66, 4, 0xFFFFFF);
        $canvas->text(4, 4, 'cv', 0xFFFFFF);
        $canvas->dirty();
        $this->add($canvas);

        // Lines: butt and round caps, with alpha.
        $this->add(
            new Line(70, 242, 290, 242, width: 4, color: 0xE0E8FF),
            new Line(70, 254, 290, 254, width: 6, color: 0x80FFB040, caps: Caps::Round),
        );

        // A group is drawn as a whole: where its circles overlap at opacity 0.6 they do not add up.
        $translucent = new Group(80, 268, opacity: 0.6);
        $translucent->add(new Circle(22, 24, 22, fill: 0xE04040), new Circle(46, 24, 22, fill: 0x4080F0));
        $blurred = new Group(205, 268, blur: 4);
        $blurred->add(new Circle(22, 24, 18, fill: 0xFFFFFF), new Text(48, 16, 'blur'));
        $this->add($translucent, $blurred);

        $this->add(new Text(180, 322, 'opacity  |  blur', color: 0x8090B0, align: Align::Center));
    }

    public function onTouch(TouchEvent $e): void
    {
        global $slate, $second;
        if ($e instanceof Pressed) {
            $slate->show($second);
        }
    }
}

final class Second extends Scene
{
    protected function build(): void
    {
        $this->add(
            new RoundRect(0, 0, 360, 360, fill: 0x14081C),
            new Circle(180, 180, 120, fill: new RadialGradient([[0.0, 0x8A4CD0], [1.0, 0x8A4CD0, 0.0]])),
            new Arc(180, 180, 100, 0, 360, 4, 0xA070E0),
            new Text(180, 156, 'Second page', align: Align::Center),
            new Text(180, 184, 'touch: back', color: 0xC8B0F0, align: Align::Center),
        );
    }

    public function onTouch(TouchEvent $e): void
    {
        global $slate, $home;
        if ($e instanceof Pressed) {
            $slate->show($home);
        }
    }
}

$slate  = Slate::onboard();
$home   = new Home();
$second = new Second();
$slate->show($home);

echo "slate-hello: touch the screen to switch page\n";
