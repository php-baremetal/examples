<?php
/**
 * touch-paint: paint with a finger. No loop(): the CST816S raises its events from the INT line and the
 * listeners draw.
 *
 * - Pressed and Moved draw a filled circle of radius 4 at the finger, in the current colour.
 * - A double tap clears the screen.
 * - A swipe to the right picks the next colour, a swipe to the left the previous one.
 * - Every gesture is printed on the console.
 *
 * St77916::onboard() and Cst816s::onboard() take their pins from the board's `display`, `i2c` and `touch` blocks.
 */

use Baremetal\Events;
use Baremetal\I2c\Driver\Cst816s;
use Baremetal\Input\Gesture;
use Baremetal\Input\GestureType;
use Baremetal\Input\Moved;
use Baremetal\Input\Pressed;
use Baremetal\Spi\Driver\St77916;

const RADIUS = 4;

$lcd = St77916::onboard();

$bg     = $lcd->rgb(0, 0, 0);
$colors = [
    $lcd->rgb(255, 255, 255),
    $lcd->rgb(255, 0, 0),
    $lcd->rgb(255, 160, 0),
    $lcd->rgb(0, 220, 0),
    $lcd->rgb(0, 160, 255),
    $lcd->rgb(200, 0, 255),
];
$current = 0;

$lcd->clear($bg);
$lcd->flush();

function paint(int $x, int $y): void
{
    global $lcd, $colors, $current;

    $lcd->fillCircle($x, $y, RADIUS, $colors[$current]);
    $lcd->flush($x - RADIUS, $y - RADIUS, $x + RADIUS + 1, $y + RADIUS + 1);
}

Events::listen(Pressed::class, fn(Pressed $e) => paint($e->x, $e->y));
Events::listen(Moved::class, fn(Moved $e) => paint($e->x, $e->y));

Events::listen(Gesture::class, function (Gesture $e): void {
    global $lcd, $bg, $colors, $current;

    printf("gesture %s at %d,%d\n", $e->type->value, $e->x, $e->y);

    switch ($e->type) {
        case GestureType::DoubleTap:
            $lcd->clear($bg);
            $lcd->flush();
            break;
        case GestureType::SwipeRight:
            $current = ($current + 1) % count($colors);
            break;
        case GestureType::SwipeLeft:
            $current = ($current + count($colors) - 1) % count($colors);
            break;
        default:
            break;   // up, down, tap and long press: printed only
    }
});

Cst816s::onboard()->events();

echo "touch-paint: draw with a finger; double tap clears; swipe left or right changes the colour\n";
