<?php
/**
 * Draw shapes and text on the ST77916 round panel, then animate a square with region flushes and show the
 * frame rate on the screen.
 *
 * St77916::onboard() opens the panel with the pins of the board's `display` block. Drawing goes to a
 * framebuffer; flush() sends it to the panel.
 */

use Baremetal\Spi\Driver\St77916;

$lcd = St77916::onboard();

$bg    = $lcd->rgb(0, 0, 48);
$white = $lcd->rgb(255, 255, 255);

$lcd->clear($bg);
$colors = [$lcd->rgb(255, 0, 0), $lcd->rgb(0, 255, 0), $lcd->rgb(0, 0, 255), $white];
foreach ($colors as $i => $color) {
    $lcd->fillRect(100 + $i * 40, 50, 40, 40, $color);
}
$lcd->circle(180, 150, 40, $lcd->rgb(255, 160, 0));
$lcd->text(180 - intdiv($lcd->textWidth('Hello'), 2), 142, 'Hello', $white);
$lcd->flush();

const BAND_Y = 270;
const SQUARE = 20;
const MIN_X = 60;
const MAX_X = 280;
const SPEED = 120;             // pixels per second
const FRAME_NS = 16_666_667;   // about 60 frames per second, the panel's refresh rate
const FPS_Y = 305;             // the frame-rate line, under the square
const FPS_BOX = [110, 140];    // x and width of the area cleared before each update

$x        = MIN_X;
$dir      = 1;
$frames   = 0;
$since    = hrtime(true);
$last     = $since;
$deadline = $since;   // when the next frame is due

function showFps(float $fps): void
{
    global $lcd, $bg, $white;
    static $shown = null;

    $label = sprintf('%.1f fps', $fps);
    echo $label, "\n";
    if ($label === $shown) {
        return;   // same value as on the screen: nothing to redraw
    }
    $shown = $label;
    $lcd->fillRect(FPS_BOX[0], FPS_Y, FPS_BOX[1], 20, $bg);
    $lcd->text(180 - intdiv($lcd->textWidth($label), 2), FPS_Y, $label, $white);
    $lcd->flush(FPS_BOX[0], FPS_Y, FPS_BOX[0] + FPS_BOX[1], FPS_Y + 20);
}

function loop(int $tick): void
{
    global $lcd, $bg, $x, $dir, $frames, $since, $last, $deadline;

    $now = hrtime(true);
    $previous = (int) $x;
    $x += $dir * SPEED * ($now - $last) / 1e9;   // move by the time that passed, not by the frame
    $last = $now;
    if ($x <= MIN_X || $x >= MAX_X) {
        $x = max(MIN_X, min(MAX_X, $x));
        $dir = -$dir;
    }
    $cur = (int) $x;

    $lcd->fillRect($previous, BAND_Y, SQUARE, SQUARE, $bg);
    $lcd->fillRect($cur, BAND_Y, SQUARE, SQUARE, $lcd->rgb(255, 200, 0));
    $lcd->flush(min($previous, $cur), BAND_Y, max($previous, $cur) + SQUARE, BAND_Y + SQUARE);

    $frames++;
    if ($now - $since >= 1_000_000_000) {
        showFps($frames * 1e9 / ($now - $since));
        $frames = 0;
        $since  = $now;
    }

    // Sleep until the next frame is due: drawing faster than the panel refreshes only makes the square jump.
    // The deadline moves by exactly one frame each time, so a sleep that runs long (usleep() rounds up to the
    // 1 ms scheduler tick) is made up by the next one and the average stays at 60 fps. A loop that falls more
    // than a frame behind starts again from now instead of rushing to catch up.
    $deadline += FRAME_NS;
    $wait = $deadline - hrtime(true);
    if ($wait > 0) {
        usleep((int) ($wait / 1000));
    } elseif ($wait < -FRAME_NS) {
        $deadline = hrtime(true);
    }
}
