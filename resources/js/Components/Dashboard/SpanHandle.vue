<script setup lang="ts">
/**
 * SpanHandle — Draggable resize handle between peer slots.
 *
 * Sits between two peer cards and allows the user to adjust
 * their relative span sizes by dragging left/right.
 *
 * The handle converts pixel delta to grid-column increments
 * and emits negotiation events for the parent to process.
 */
import { ref } from 'vue'

const props = defineProps<{
    /** Width of the parent container in pixels */
    containerWidth: number
    /** Total span shared between the two peers */
    totalSpan: number
    /** Disable interaction (e.g. in narrow/responsive mode) */
    disabled?: boolean
}>()

const emit = defineEmits<{
    (e: 'negotiate', delta: number): void
}>()

const dragging = ref(false)

let startX = 0
let accumulatedDelta = 0

const colWidth = () => props.containerWidth / props.totalSpan

const onPointerDown = (e: PointerEvent) => {
    if (props.disabled) return
    e.preventDefault()
    e.stopPropagation()
    dragging.value = true
    startX = e.clientX
    accumulatedDelta = 0
    document.body.style.cursor = 'col-resize'
    document.body.style.userSelect = 'none'
    document.addEventListener('pointermove', onPointerMove)
    document.addEventListener('pointerup', onPointerUp)
}

const onPointerMove = (e: PointerEvent) => {
    const pixelDelta = e.clientX - startX
    const cw = colWidth()
    if (cw <= 0) return

    const spanDelta = Math.round(pixelDelta / cw)
    if (spanDelta !== accumulatedDelta) {
        emit('negotiate', spanDelta - accumulatedDelta)
        accumulatedDelta = spanDelta
    }
}

const onPointerUp = () => {
    dragging.value = false
    document.body.style.cursor = ''
    document.body.style.userSelect = ''
    document.removeEventListener('pointermove', onPointerMove)
    document.removeEventListener('pointerup', onPointerUp)
}
</script>

<template>
    <div
        class="relative flex-shrink-0 z-10 group/handle select-none"
        :class="[
            disabled ? 'cursor-default w-0' : 'cursor-col-resize',
            dragging ? 'w-2' : 'w-5 -mx-1',
        ]"
        @pointerdown="onPointerDown"
    >
        <!-- Visual track -->
        <div
            v-if="!disabled"
            class="absolute inset-y-4 left-1/2 -translate-x-1/2 w-1 rounded-full transition-colors duration-150"
            :class="dragging ? 'bg-indigo-500' : 'bg-gray-200 group-hover/handle:bg-indigo-300'"
        ></div>

        <!-- Grip dots (visible on hover) -->
        <div
            v-if="!disabled && !dragging"
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0 group-hover/handle:opacity-100 transition-opacity pointer-events-none"
        >
            <div class="flex flex-col gap-1">
                <div class="flex gap-0.5">
                    <div class="w-1 h-1 rounded-full bg-indigo-400"></div>
                    <div class="w-1 h-1 rounded-full bg-indigo-400"></div>
                </div>
                <div class="flex gap-0.5">
                    <div class="w-1 h-1 rounded-full bg-indigo-400"></div>
                    <div class="w-1 h-1 rounded-full bg-indigo-400"></div>
                </div>
                <div class="flex gap-0.5">
                    <div class="w-1 h-1 rounded-full bg-indigo-400"></div>
                    <div class="w-1 h-1 rounded-full bg-indigo-400"></div>
                </div>
            </div>
        </div>

        <!-- Active state indicator -->
        <div
            v-if="dragging"
            class="absolute inset-y-2 left-1/2 -translate-x-1/2 w-0.5 bg-indigo-500 rounded-full shadow-sm shadow-indigo-200"
        ></div>
    </div>
</template>
