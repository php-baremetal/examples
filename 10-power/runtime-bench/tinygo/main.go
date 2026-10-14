// runtime-bench, TinyGo: the idle and busy phases of the php-baremetal bench in ../php, 30 s each, repeating.
// TinyGo has no light or deep sleep for the ESP32-S3, so those two phases are left out.
// The CPU is set to 160 MHz like the other benches (TinyGo starts at 240 MHz).
package main

import (
	"machine"
	"time"
)

const phase = 30 * time.Second

func main() {
	if err := machine.SetCPUFrequency(160_000000); err != nil {
		println("cpu frequency:", err.Error())
	}
	f, _ := machine.GetCPUFrequency()
	println("runtime-bench (TinyGo): idle / busy, 30 s each, cpu", f/1000000, "MHz")
	for {
		println("[IDLE]")
		time.Sleep(phase)
		println("[BUSY]")
		end := time.Now().Add(phase)
		for time.Now().Before(end) {
		}
	}
}
