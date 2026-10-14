<?php

// 00-hello -- the simplest firmware: prove a packaged engine extension (ext-hello) loads and runs.
// Requires only the neutral packages (ext-events, ext-hello) + platform=esp32; conductor binds the
// arch backing (esp32-events-hal) on its own.

echo bm_hello(), "\n";          // "hello from ext-hello package" -- from the ext-hello package

function loop(int $t): void
{
    if ($t % 5 === 0) {
        echo "tick {$t} -- still alive\n";
    }
    delay(1000);
}
