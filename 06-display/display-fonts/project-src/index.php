<?php
/**
 * Text with the four font sources of ext-display:
 *   default         built into ext-display
 *   title           baked from fonts-src/DejaVuSans.ttf (extra.baremetal.fonts.bake)
 *   clock-48        project-fonts/clock-48.h
 *   dejavu-sans-16  the php-baremetal/font-dejavu package
 */

use Baremetal\Display\Font;
use Baremetal\Spi\Driver\St77916;

$lcd = St77916::onboard();

$bg     = $lcd->rgb(0, 0, 48);
$white  = $lcd->rgb(255, 255, 255);
$amber  = $lcd->rgb(255, 200, 0);
$grey   = $lcd->rgb(160, 200, 255);
$width  = $lcd->width();

$title = Font::get('title');
$clock = Font::get('clock-48');
$dejavu = Font::get('dejavu-sans-16');

function centered(string $s, int $y, int $color, ?int $bg = null, ?Font $font = null): void
{
    global $lcd, $width;
    $lcd->text(intdiv($width - $lcd->textWidth($s, $font), 2), $y, $s, $color, $bg, $font);
}

$lcd->clear($bg);
centered('Città 21°C', 40, $white, $bg, $title);
centered('00:00', 100, $amber, $bg, $clock);
centered('Temperatura àèìòù °', 180, $grey, $bg, $dejavu);
centered('default: àèìòù °', 215, $white, $bg);
$lcd->flush();

$clockW = $lcd->textWidth('00:00', $clock);
$clockX = intdiv($width - $clockW, 2);
$clockY = 100;
$shown  = -1;
$start  = hrtime(true);

function loop(int $tick): void
{
    global $lcd, $bg, $amber, $clock, $clockX, $clockY, $clockW, $shown, $start;

    // hrtime() is a float on the board's 32-bit PHP: divide, then truncate (intdiv() rejects it after ~2 s).
    $seconds = (int) ((hrtime(true) - $start) / 1e9);
    if ($seconds !== $shown) {
        $shown = $seconds;
        $text  = sprintf('%02d:%02d', intdiv($seconds, 60) % 60, $seconds % 60);
        $lcd->fillRect($clockX, $clockY, $clockW, $clock->height(), $bg);
        $lcd->text($clockX, $clockY, $text, $amber, $bg, $clock);
        $lcd->flush($clockX, $clockY, $clockX + $clockW, $clockY + $clock->height());
    }
    delay(100);
}
