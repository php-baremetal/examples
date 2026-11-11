<?php
/**
 * slate-images: pictures with Slate on the round 360 x 360 panel.
 *
 * - The icons are PNG files of assets/ui: phpbm converts them to .sli and puts them in the firmware's flash, at
 *   /app/assets/ui/icons/<name>.sli.
 * - The background photos are PNG files of assets/photos: phpbm converts them too and copies them to the microSD
 *   card, at /sdcard/assets/photos/<name>.sli.
 * - /sdcard/picture.png is a PNG you copy to the card yourself: it is decoded on the board.
 * - The second page is preloaded while the first is shown: a touch shows it complete at its first frame.
 *
 * Every image loads in the background: the page appears at once and each picture shows up when it is in memory.
 */

use Baremetal\Events;
use Baremetal\Input\Pressed;
use Baremetal\Input\TouchEvent;
use Baremetal\Slate\{Align, Image, ImageError, ImageFailed, ImageLoaded, Images, PreloadDone, RoundRect, Scene, Slate, Text};

const PICTURE = '/sdcard/picture.png';

final class Home extends Scene
{
    public Image $picture;
    public Text $hint;

    protected function build(): void
    {
        // Shown until the photo is loaded (and then hidden under it: an opaque image covers what is below).
        $this->add(new RoundRect(0, 0, 360, 360, fill: 0x101828));
        $this->add(new Image(0, 0, '/sdcard/assets/photos/evening.sli'));

        $this->add(new Text(180, 34, 'Slate images', align: Align::Center));

        // Four icons with transparent corners and a soft shadow, from the flash.
        foreach (['sun', 'drop', 'leaf', 'bolt'] as $i => $name) {
            $this->add(new Image(30 + $i * 76, 66, "/app/assets/ui/icons/{$name}.sli"));
        }

        // A PNG put on the card by hand: centred when it is loaded, a hint when it is missing.
        $this->picture = new Image(100, 160, PICTURE);
        $this->hint = new Text(180, 200, 'loading picture.png ...', color: 0xC0D0F0, align: Align::Center);
        $this->add($this->picture, $this->hint);

        $this->add(new Text(180, 300, 'touch: next page', color: 0xE0E8FF, align: Align::Center));
    }

    public function onTouch(TouchEvent $e): void
    {
        global $slate, $dunes;
        if ($e instanceof Pressed) {
            $dunes ??= new Dunes();   // built at its first show(): its images are in memory already
            $slate->show($dunes);
        }
    }
}

final class Dunes extends Scene
{
    protected function build(): void
    {
        $this->add(
            new RoundRect(0, 0, 360, 360, fill: 0x5BB4E8),
            new Image(0, 0, '/sdcard/assets/photos/dunes.sli'),
            new Image(144, 60, '/app/assets/ui/icons/sun.sli'),
            new Text(180, 160, 'Second page', color: 0x1B2A5A, align: Align::Center),
            new Text(180, 186, 'preloaded: touch to go back', color: 0x1B2A5A, align: Align::Center),
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

Events::listen(ImageLoaded::class, function (ImageLoaded $e) use (&$home): void {
    echo "loaded {$e->path} ({$e->w} x {$e->h})\n";
    if ($e->path === PICTURE) {
        $home->picture->set(x: 180 - intdiv($e->w, 2), y: 210 - intdiv($e->h, 2));
        $home->hint->visible = false;
    }
});

Events::listen(ImageFailed::class, function (ImageFailed $e) use (&$home): void {
    echo "failed {$e->path}: {$e->error->value}, {$e->message}\n";
    if ($e->path === PICTURE) {
        $home->hint->text = $e->error === ImageError::NotFound
            ? 'copy a PNG to /sdcard/picture.png'
            : "picture.png: {$e->error->value}";
    }
});

$slate = Slate::onboard();
$home  = new Home();
$dunes = null;
$slate->show($home);

// The images of the next page, loaded after the ones of this page (one at a time, in order).
$next = Images::preload(['/sdcard/assets/photos/dunes.sli', '/app/assets/ui/icons/sun.sli']);
Events::listen(PreloadDone::class, function (PreloadDone $e) use ($next): void {
    if ($next->done) {
        echo 'next page ready', $e->failed ? ', failed: ' . implode(', ', $e->failed) : '', "\n";
    }
});

echo "slate-images: touch the screen to switch page\n";
