<?php
/**
 * display-vsync: a 120 px tall rectangle runs across the round panel at 240 px/s. Each frame redraws only the
 * band the rectangle touches and flushes it; flush() returns before the transfer ends.
 *
 * With VSYNC = true every flush starts at the next pulse of the panel's TE pin, so the panel never shows half
 * of an old frame. With false it starts at once and the edge of the rectangle can tear.
 *
 * onVsync() is the frame clock; onFlushed() reports the transfer time of each region. Once a second the
 * console prints the frames per second and the mean transfer time.
 */

use Baremetal\Display\Flushed;
use Baremetal\Events;
use Baremetal\Spi\Driver\St77916;
use Baremetal\TimerEvent;

const VSYNC = true;
const RECT_W = 60;
const RECT_H = 120;
const RECT_Y = 120;
const SPEED = 240;             // pixels per second

final class Frame extends TimerEvent {}
final class Done extends Flushed {}

$lcd = St77916::onboard(vsync: VSYNC);

$bg = $lcd->rgb(0, 0, 48);
$fg = $lcd->rgb(255, 200, 0);
$w  = $lcd->width();
$max = $w - RECT_W;

$lcd->clear($bg);
$lcd->flush();

$x      = 0.0;
$dir    = 1;
$last   = hrtime(true);
$frames = 0;
$since  = $last;
$usSum  = 0;
$usN    = 0;

Events::listen(Done::class, function (Done $e): void {
    global $usSum, $usN;
    $usSum += $e->us;
    $usN++;
});

Events::listen(Frame::class, function (Frame $e): void {
    global $lcd, $bg, $fg, $x, $dir, $last, $max, $frames, $since, $usSum, $usN;

    $previous = (int) $x;
    $x += $dir * SPEED * ($e->time - $last) / 1e9;   // by the time that passed, not by the event
    $last = $e->time;
    if ($x <= 0 || $x >= $max) {
        $x = max(0.0, min((float) $max, $x));
        $dir = -$dir;
    }
    $cur = (int) $x;

    $lcd->fillRect($previous, RECT_Y, RECT_W, RECT_H, $bg);
    $lcd->fillRect($cur, RECT_Y, RECT_W, RECT_H, $fg);
    $lcd->flush(min($previous, $cur), RECT_Y, max($previous, $cur) + RECT_W, RECT_Y + RECT_H);

    $frames++;
    $now = $e->time;
    if ($now - $since >= 1_000_000_000) {
        printf("%.1f fps  transfer %d us  vsync %s\n",
            $frames * 1e9 / ($now - $since), $usN ? intdiv($usSum, $usN) : 0, VSYNC ? 'on' : 'off');
        $frames = 0;
        $usSum  = 0;
        $usN    = 0;
        $since  = $now;
    }
});

$lcd->onFlushed(Done::class);
$lcd->onVsync(Frame::class);

printf("display-vsync: vsync %s, %d px/s\n", VSYNC ? 'on' : 'off', SPEED);
