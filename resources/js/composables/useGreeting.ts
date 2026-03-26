/**
 * ─────────────────────────────────────────────────────────────
 * useGreeting — Dynamic Time-Aware Greeting (PH Timezone)
 * ─────────────────────────────────────────────────────────────
 *
 * Returns a reactive greeting based on Philippines Time (UTC+8).
 * Updates every minute.
 */

import { ref, onMounted, onUnmounted, computed } from 'vue'

function getPhilippinesHour(): number {
    const now = new Date()
    // Convert to Philippines time (UTC+8)
    const utc = now.getTime() + now.getTimezoneOffset() * 60000
    const phTime = new Date(utc + 8 * 3600000)
    return phTime.getHours()
}

function getGreetingText(hour: number): string {
    if (hour >= 5 && hour < 12) return 'Good Morning'
    if (hour >= 12 && hour < 17) return 'Good Afternoon'
    if (hour >= 17 && hour < 21) return 'Good Evening'
    return 'Good Evening'
}

function getPhilippinesTimeString(): string {
    const now = new Date()
    const utc = now.getTime() + now.getTimezoneOffset() * 60000
    const phTime = new Date(utc + 8 * 3600000)
    return phTime.toLocaleTimeString('en-PH', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    })
}

function getPhilippinesDateString(): string {
    const now = new Date()
    const utc = now.getTime() + now.getTimezoneOffset() * 60000
    const phTime = new Date(utc + 8 * 3600000)
    return phTime.toLocaleDateString('en-PH', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

export function useGreeting() {
    const currentHour = ref(getPhilippinesHour())
    const currentTime = ref(getPhilippinesTimeString())
    const currentDate = ref(getPhilippinesDateString())

    let interval: ReturnType<typeof setInterval> | null = null

    const greeting = computed(() => getGreetingText(currentHour.value))

    onMounted(() => {
        interval = setInterval(() => {
            currentHour.value = getPhilippinesHour()
            currentTime.value = getPhilippinesTimeString()
            currentDate.value = getPhilippinesDateString()
        }, 30000) // Update every 30 seconds
    })

    onUnmounted(() => {
        if (interval) clearInterval(interval)
    })

    return {
        greeting,
        currentTime,
        currentDate,
    }
}
