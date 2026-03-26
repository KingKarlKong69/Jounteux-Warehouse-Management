<template>
    <div
        :class="[
            'animate-pulse rounded bg-gray-200 dark:bg-gray-700',
            widthClass,
            heightClass,
            className,
        ]"
        :style="customStyle"
        role="status"
        aria-hidden="true"
    />
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
    /** Tailwind width class (e.g. 'w-full', 'w-24') */
    width?: string
    /** Tailwind height class (e.g. 'h-4', 'h-8') */
    height?: string
    /** Make it a circle (avatar placeholder) */
    circle?: boolean
    /** Extra Tailwind classes */
    className?: string
}>(), {
    width: 'w-full',
    height: 'h-4',
    circle: false,
    className: '',
})

const widthClass = computed(() => props.circle ? '' : props.width)
const heightClass = computed(() => props.circle ? '' : props.height)

const customStyle = computed(() => {
    if (props.circle) {
        // Circle: width = height
        return {}
    }
    return {}
})
</script>
