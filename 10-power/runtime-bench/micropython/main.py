# runtime-bench, MicroPython: the same four 30 s phases as the php-baremetal bench in ../php.
#   0-30 s idle in time.sleep(), 30-60 s busy loop, 60-90 s machine.lightsleep(), 90-120 s machine.deepsleep().
# The deep sleep reboots and main.py runs again, so the cycle repeats.
import machine
import time

PHASE = 30

print("runtime-bench (MicroPython): idle / busy / light sleep / deep sleep, %d s each" % PHASE)
print("[IDLE]")
time.sleep(PHASE)
print("[BUSY]")
end = time.ticks_add(time.ticks_ms(), PHASE * 1000)
while time.ticks_diff(end, time.ticks_ms()) > 0:
    pass
print("[LIGHT SLEEP]")
machine.lightsleep(PHASE * 1000)
print("[DEEP SLEEP]")
machine.deepsleep(PHASE * 1000)
