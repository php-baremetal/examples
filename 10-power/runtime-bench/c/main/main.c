#include <stdio.h>

#include "freertos/FreeRTOS.h"
#include "freertos/task.h"
#include "esp_sleep.h"
#include "esp_timer.h"

/* runtime-bench, plain ESP-IDF C: the same four 30 s phases as the php-baremetal bench in ../php.
 *   0-30 s idle in vTaskDelay(), 30-60 s busy loop, 60-90 s light sleep, 90-120 s deep sleep, then a reboot. */
#define PHASE_S 30

void app_main(void)
{
    printf("runtime-bench (C): idle / busy / light sleep / deep sleep, %d s each\n", PHASE_S);

    printf("[IDLE]\n");
    vTaskDelay(pdMS_TO_TICKS(PHASE_S * 1000));

    printf("[BUSY]\n");
    int64_t end = esp_timer_get_time() + PHASE_S * 1000000LL;
    while (esp_timer_get_time() < end) {
    }

    printf("[LIGHT SLEEP]\n");
    fflush(stdout);
    esp_sleep_enable_timer_wakeup(PHASE_S * 1000000ULL);
    esp_light_sleep_start();

    printf("[DEEP SLEEP]\n");
    fflush(stdout);
    esp_deep_sleep(PHASE_S * 1000000ULL);
}
