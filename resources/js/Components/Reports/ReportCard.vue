<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { useCardResize, type CardSize } from '@/composables/useCardResize'
import {
    ArrowsPointingOutIcon,
    ArrowsPointingInIcon,
    ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps<{
    id: string
    title: string
    subtitle?: string
    icon: any
    iconColor: string
    navigateTo?: string
    expanded: boolean
    initialSize?: CardSize
}>()

const emit = defineEmits<{
    (e: 'toggle-expand'): void
    (e: 'resize-end', size: CardSize): void
}>()

// ─── Resize logic ─────────────────────────────────────────────
const defaultSize: CardSize = { width: null, height: 340, colSpan: 1 }

const { size, resizing, startResize } = useCardResize(
    props.initialSize ?? defaultSize,
    (s) => emit('resize-end', s),
)

// When expand changes, override height
watch(() => props.expanded, (exp) => {
    if (exp) {
        size.value = { ...size.value, height: 560 }
    } else if (props.initialSize) {
        size.value = { ...props.initialSize }
    } else {
        size.value = { ...defaultSize }
    }
    nextTick(() => window.dispatchEvent(new Event('resize')))
})

const cardStyle = computed(() => {
    const s: Record<string, string> = {}
    if (!props.expanded) {
        if (size.value.width != null) s.width = `${size.value.width}px`
        s.height = `${size.value.height}px`
        // Strict overflow prevention
        s.maxWidth = '100%'
    }
    return s
})

const navigate = () => {
    if (props.navigateTo) router.visit(props.navigateTo)
}
</script>

<template>
    <div data-report-card
         class="bg-white dark:bg-gray-800/90 rounded-2xl shadow-sm border border-gray-200/60 dark:border-gray-700/50 flex flex-col group relative select-none overflow-hidden"
         :class="[
             expanded ? 'col-span-full row-span-2' : '',
             resizing ? 'transition-none' : 'transition-all duration-200',
             resizing ? 'shadow-lg ring-2 ring-orange-300/50 dark:ring-orange-500/30' : 'hover:shadow-md dark:hover:shadow-lg dark:hover:shadow-black/20',
         ]"
         :style="cardStyle">

        <!-- ═══ Resize Handles ═══ -->
        <div class="absolute bottom-0 left-4 right-4 h-1.5 cursor-s-resize z-20 group/handle hover:bg-orange-400/30 rounded-full transition-colors"
             @pointerdown="(e: PointerEvent) => startResize(e, 's')">
            <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-8 h-1 rounded-full bg-gray-300 dark:bg-gray-600 opacity-0 group-hover/handle:opacity-100 group-hover:opacity-60 transition-opacity"></div>
        </div>
        <div class="absolute top-4 bottom-4 right-0 w-1.5 cursor-e-resize z-20 hover:bg-orange-400/30 rounded-full transition-colors"
             @pointerdown="(e: PointerEvent) => startResize(e, 'e')"></div>
        <div class="absolute top-4 bottom-4 left-0 w-1.5 cursor-w-resize z-20 hover:bg-orange-400/30 rounded-full transition-colors"
             @pointerdown="(e: PointerEvent) => startResize(e, 'w')"></div>
        <div class="absolute top-0 left-4 right-4 h-1.5 cursor-n-resize z-20 hover:bg-orange-400/30 rounded-full transition-colors"
             @pointerdown="(e: PointerEvent) => startResize(e, 'n')"></div>
        <!-- Corners -->
        <div class="absolute bottom-0 right-0 w-3 h-3 cursor-se-resize z-30 hover:bg-orange-400/40 rounded-tl transition-colors"
             @pointerdown="(e: PointerEvent) => startResize(e, 'se')">
            <svg class="w-3 h-3 text-gray-400 dark:text-gray-600 opacity-0 group-hover:opacity-60 transition-opacity" viewBox="0 0 12 12">
                <path d="M10 2v8H2" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="absolute bottom-0 left-0 w-3 h-3 cursor-sw-resize z-30 hover:bg-orange-400/40 rounded-tr transition-colors"
             @pointerdown="(e: PointerEvent) => startResize(e, 'sw')"></div>
        <div class="absolute top-0 right-0 w-3 h-3 cursor-ne-resize z-30 hover:bg-orange-400/40 rounded-bl transition-colors"
             @pointerdown="(e: PointerEvent) => startResize(e, 'ne')"></div>
        <div class="absolute top-0 left-0 w-3 h-3 cursor-nw-resize z-30 hover:bg-orange-400/40 rounded-br transition-colors"
             @pointerdown="(e: PointerEvent) => startResize(e, 'nw')"></div>

        <!-- ═══ Drag Handle ═══ -->
        <div class="drag-handle absolute top-0 left-1/2 -translate-x-1/2 z-40 w-10 h-1.5 rounded-b-full bg-gray-300 dark:bg-gray-600 cursor-grab active:cursor-grabbing opacity-0 group-hover:opacity-100 transition-opacity"></div>

        <!-- ═══ Card Header ═══ -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 bg-gradient-to-r from-gray-50/80 to-white dark:from-gray-800/50 dark:to-gray-800/30 shrink-0">
            <div class="flex items-center gap-3 min-w-0 flex-1 cursor-pointer" @click="navigate">
                <div class="shrink-0 flex items-center justify-center w-9 h-9 rounded-xl shadow-sm" :class="iconColor">
                    <component :is="icon" class="h-5 w-5 text-white" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ title }}</h3>
                    <p v-if="subtitle" class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ subtitle }}</p>
                </div>
            </div>

            <div class="flex items-center gap-1 shrink-0">
                <button v-if="navigateTo"
                    @click="navigate"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 dark:hover:text-orange-400 transition-colors"
                    title="Open module">
                    <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                </button>
                <button
                    @click="emit('toggle-expand')"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 dark:hover:text-orange-400 transition-colors"
                    :title="expanded ? 'Collapse' : 'Expand'">
                    <ArrowsPointingInIcon v-if="expanded" class="h-4 w-4" />
                    <ArrowsPointingOutIcon v-else class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- ═══ Card Content ═══ -->
        <div class="flex-1 p-4 min-h-0 overflow-hidden">
            <slot :expanded="expanded" />
        </div>
    </div>
</template>
