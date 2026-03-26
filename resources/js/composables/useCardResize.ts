import { ref, onUnmounted, type Ref } from 'vue'

export interface CardSize {
    width: number | null   // px — null means "auto / grid-controlled"
    height: number         // px
    colSpan: number        // 1–3 grid columns
}

const MIN_WIDTH  = 280
const MIN_HEIGHT = 200
const MAX_HEIGHT = 700
const MAX_WIDTH  = 1200  // Strict max width to prevent overflow

type Edge = 'n' | 's' | 'e' | 'w' | 'ne' | 'nw' | 'se' | 'sw'

/**
 * Composable for edge/corner resize of a card element.
 * Returns reactive size + pointer-event handlers for each handle.
 *
 * Strict constraints:
 * - Max width clamped to container width (never exceeds viewport)
 * - Max height capped at 700px
 * - Width snaps to grid-friendly increments (40px)
 * - Height snaps to 20px increments
 */
export function useCardResize(
    initialSize: CardSize,
    onResizeEnd?: (size: CardSize) => void,
) {
    const size = ref<CardSize>({ ...initialSize })
    const resizing = ref(false)

    let startX  = 0
    let startY  = 0
    let startW  = 0
    let startH  = 0
    let edge: Edge = 's'
    let containerMaxW = MAX_WIDTH

    /**
     * Snap value to nearest grid increment for clean layout alignment.
     */
    const snapToGrid = (value: number, gridSize: number) => {
        return Math.round(value / gridSize) * gridSize
    }

    /**
     * Get the max allowed width based on the card's parent container.
     */
    const getContainerMaxWidth = (el: HTMLElement | null): number => {
        if (!el) return MAX_WIDTH
        const gridParent = el.closest('.grid, [class*="grid"]') as HTMLElement | null
        if (gridParent) {
            const parentRect = gridParent.getBoundingClientRect()
            return parentRect.width - 24 // Account for gap/padding
        }
        return Math.min(MAX_WIDTH, window.innerWidth - 80) // Sidebar offset
    }

    const onPointerMove = (e: PointerEvent) => {
        const dx = e.clientX - startX
        const dy = e.clientY - startY

        let newW = startW
        let newH = startH

        // Vertical
        if (edge.includes('s')) newH = startH + dy
        if (edge.includes('n')) newH = startH - dy

        // Horizontal
        if (edge.includes('e')) newW = startW + dx
        if (edge.includes('w')) newW = startW - dx

        // Clamp height with grid snapping
        newH = snapToGrid(Math.max(MIN_HEIGHT, Math.min(MAX_HEIGHT, newH)), 20)

        // Clamp width strictly — never exceed container
        if (newW !== startW) {
            newW = snapToGrid(Math.max(MIN_WIDTH, Math.min(containerMaxW, newW)), 40)
        }

        size.value = {
            width: edge.includes('e') || edge.includes('w') ? newW : size.value.width,
            height: newH,
            colSpan: size.value.colSpan,
        }
    }

    const onPointerUp = () => {
        resizing.value = false
        document.removeEventListener('pointermove', onPointerMove)
        document.removeEventListener('pointerup', onPointerUp)
        document.body.style.cursor = ''
        document.body.style.userSelect = ''
        onResizeEnd?.(size.value)
    }

    const startResize = (e: PointerEvent, handle: Edge) => {
        e.preventDefault()
        e.stopPropagation()
        resizing.value = true
        edge   = handle
        startX = e.clientX
        startY = e.clientY

        // Measure current actual rendered size and calculate container bounds
        const el = (e.target as HTMLElement).closest('[data-report-card]') as HTMLElement | null
        if (el) {
            const rect = el.getBoundingClientRect()
            startW = rect.width
            startH = rect.height
            containerMaxW = getContainerMaxWidth(el)
        } else {
            startW = size.value.width ?? 400
            startH = size.value.height
            containerMaxW = MAX_WIDTH
        }

        const cursorMap: Record<Edge, string> = {
            n: 'ns-resize', s: 'ns-resize',
            e: 'ew-resize', w: 'ew-resize',
            ne: 'nesw-resize', sw: 'nesw-resize',
            nw: 'nwse-resize', se: 'nwse-resize',
        }
        document.body.style.cursor     = cursorMap[handle]
        document.body.style.userSelect = 'none'

        document.addEventListener('pointermove', onPointerMove)
        document.addEventListener('pointerup', onPointerUp)
    }

    onUnmounted(() => {
        document.removeEventListener('pointermove', onPointerMove)
        document.removeEventListener('pointerup', onPointerUp)
    })

    return { size, resizing, startResize }
}
