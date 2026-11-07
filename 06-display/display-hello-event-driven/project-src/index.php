<?php
/**
 * display-hello in the event-driven runtime: no loop(). The script draws the first frame, registers a
 * listener and asks the panel for its own vertical sync (the TE pin); from then on the reactor delivers one
 * Frame event per refresh and the listener moves the square, sleeping in between.
 *
 * St77916::onboard() opens the panel with the pins of the board's `display` block. Drawing goes to a
 * framebuffer; flush() starts sending it to the panel and returns before the transfer ends.
 */

use Baremetal\Events;
use Baremetal\TimerEvent;
use Baremetal\Spi\Driver\St77916;

final class Frame extends TimerEvent {}   // $ticks: 1, or more when frames were missed

const BAND_Y = 270;
const SQUARE = 20;
const MIN_X = 60;
const MAX_X = 280;
const SPEED = 120;             // pixels per second
const FPS_Y = 305;             // the frame-rate line, under the square
const FPS_BOX = [110, 140];    // x and width of the area cleared before each update

$lcd = St77916::onboard();

$bg     = $lcd->rgb(0, 0, 48);
$white  = $lcd->rgb(255, 255, 255);
$yellow = $lcd->rgb(255, 200, 0);

$lcd->clear($bg);
$colors = [$lcd->rgb(255, 0, 0), $lcd->rgb(0, 255, 0), $lcd->rgb(0, 0, 255), $white];
foreach ($colors as $i => $color) {
    $lcd->fillRect(100 + $i * 40, 50, 40, 40, $color);
}
$lcd->circle(180, 150, 40, $lcd->rgb(255, 160, 0));
$lcd->text(180 - intdiv($lcd->textWidth('Hello'), 2), 142, 'Hello', $white);
$lcd->flush();

$x      = MIN_X;
$dir    = 1;
$frames = 0;
$since  = hrtime(true);
$last   = $since;
$latSum = 0.0;                 // sum of the delivery latencies in the current second, ns
$latN   = 0;
$shown  = null;

function showFps(float $fps, float $latUs): void
{
    global $lcd, $bg, $white, $shown;

    $label = sprintf('%.1f fps', $fps);
    printf("%s  latency %.0f us\n", $label, $latUs);
    if ($label === $shown) {
        return;   // same value as on the screen: nothing to redraw
    }
    $shown = $label;
    $lcd->fillRect(FPS_BOX[0], FPS_Y, FPS_BOX[1], 20, $bg);
    $lcd->text(180 - intdiv($lcd->textWidth($label), 2), FPS_Y, $label, $white);
    $lcd->flush(FPS_BOX[0], FPS_Y, FPS_BOX[0] + FPS_BOX[1], FPS_Y + 20);
}

Events::listen(Frame::class, function (Frame $e): void {
    global $lcd, $bg, $yellow, $x, $dir, $frames, $since, $last, $latSum, $latN;

    $now = hrtime(true);
    $latSum += $now - $e->time;   // $e->time is when the tick fired; now is when the listener runs
    $latN++;
    $previous = (int) $x;
    $t = $e->time;                               // the instant of the tick, not of the listener
    $x += $dir * SPEED * ($t - $last) / 1e9;     // move by the time that passed, not by the event
    $last = $t;
    if ($x <= MIN_X || $x >= MAX_X) {
        $x = max(MIN_X, min(MAX_X, $x));
        $dir = -$dir;
    }
    $cur = (int) $x;

    $lcd->fillRect($previous, BAND_Y, SQUARE, SQUARE, $bg);
    $lcd->fillRect($cur, BAND_Y, SQUARE, SQUARE, $yellow);
    $lcd->flush(min($previous, $cur), BAND_Y, max($previous, $cur) + SQUARE, BAND_Y + SQUARE);

    $frames++;
    if ($now - $since >= 1_000_000_000) {
        showFps($frames * 1e9 / ($now - $since), $latSum / $latN / 1e3);
        $frames = 0;
        $latSum = 0.0;
        $latN   = 0;
        $since  = $now;
    }
});

// One Frame per panel refresh, raised by the panel's TE pin.
$lcd->onVsync(Frame::class);

printf("display-hello-event-driven: a Frame at every vsync of the panel (%.0f Hz nominal)\n", $lcd->refreshRate());
