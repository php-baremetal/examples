<?php
use Baremetal\TimerEvent;
use Baremetal\PinEvent;
use Baremetal\Events;

final class Blink extends TimerEvent {}
final class Click extends PinEvent {}
final class DoubleClick extends PinEvent {}

$on = false;
Events::listen(Blink::class, function (Blink $e): void {
    global $on;
    $on = !$on;
    printf("%s  (period %.0f ms)\n", $on ? 'tick' : 'tock', $GLOBALS['timer']->period());
});
Events::listen(Click::class, function (): void {
    $t = $GLOBALS['timer'];
    $t->running() ? $t->stop() : $t->start();
    echo $t->running() ? "timer started\n" : "timer stopped\n";
});
Events::listen(DoubleClick::class, function (): void {
    $t = $GLOBALS['timer'];
    $t->setPeriod($t->period() >= 4000 ? 500 : $t->period() * 2);
    printf("period %.0f ms\n", $t->period());
});

$timer = every(500, Blink::class);
watch_button(0, ['click' => Click::class, 'double' => DoubleClick::class]);
echo "timer-control: BOOT click stops/starts the timer, double click doubles its period\n";
