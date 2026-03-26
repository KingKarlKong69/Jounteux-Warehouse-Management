import { defineStore } from 'pinia'
import type { SlotState, PersistedLayout } from '@/types/dashboard'

// ─── Constants ──────────────────────────────────────────────
const STORAGE_KEY = 'wms_dashboard_layout_v2'
const LAYOUT_VERSION = 1

// ─── Default KPI Order ──────────────────────────────────────
const DEFAULT_KPI_ORDER = [
    'total_sales_orders',
    'total_sales_value',
    'completed_sales',
    'total_products',
    'total_purchase_orders',
]

// ─── Default Slot Factory ───────────────────────────────────
function createDefaultSlots(): Record<string, SlotState> {
    return {
        'analytics-left': {
            id: 'analytics-left',
            currentCardId: 'top-products',
            span: 4,
            defaultSpan: 4,
            minSpan: 3,
            maxSpan: 5,
            zone: 'analytics',
            allowedTypes: ['secondary-chart'],
            peerSlotId: 'analytics-right',
            totalPeerSpan: 8,
        },
        'analytics-right': {
            id: 'analytics-right',
            currentCardId: 'purchase-analytics',
            span: 4,
            defaultSpan: 4,
            minSpan: 3,
            maxSpan: 5,
            zone: 'analytics',
            allowedTypes: ['secondary-chart'],
            peerSlotId: 'analytics-left',
            totalPeerSpan: 8,
        },
        'operational-left': {
            id: 'operational-left',
            currentCardId: 'supplier-procurement',
            span: 6,
            defaultSpan: 6,
            minSpan: 4,
            maxSpan: 8,
            zone: 'operational',
            allowedTypes: ['operational-chart'],
            peerSlotId: 'operational-right',
            totalPeerSpan: 12,
        },
        'operational-right': {
            id: 'operational-right',
            currentCardId: 'inventory-overview',
            span: 6,
            defaultSpan: 6,
            minSpan: 4,
            maxSpan: 8,
            zone: 'operational',
            allowedTypes: ['operational-chart'],
            peerSlotId: 'operational-left',
            totalPeerSpan: 12,
        },
    }
}

// ─── Store Definition ───────────────────────────────────────

/**
 * Reactive Grid Orchestrator — Pinia Store
 *
 * Responsibilities:
 * - Validate drag compatibility
 * - Enforce slot type rules
 * - Enforce span negotiation constraints
 * - Prevent layout corruption
 * - Persist user layout to localStorage
 */
export const useDashboardLayoutStore = defineStore('dashboardLayout', {
    state: () => ({
        /** Slot registry — positions are permanent, content is dynamic */
        slots: createDefaultSlots() as Record<string, SlotState>,

        /** KPI card ordering (isolated drag context) */
        kpiOrder: [...DEFAULT_KPI_ORDER] as string[],

        /** Currently expanded card ID (only one at a time) */
        expandedCardId: null as string | null,

        /** Pre-expansion slot spans (for restoration) */
        preExpansionSpans: {} as Record<string, number>,

        /** Responsive mode flag — disables span negotiation */
        isNarrow: false,
    }),

    getters: {
        /**
         * Get a specific slot by ID.
         */
        getSlot: (state) => (id: string): SlotState | undefined => state.slots[id],

        /**
         * Check if a card is currently expanded.
         */
        isExpanded: (state) => (cardId: string): boolean => state.expandedCardId === cardId,

        /**
         * Find which slot contains a given card.
         */
        getSlotByCardId: (state) => (cardId: string): SlotState | undefined => {
            return Object.values(state.slots).find(s => s.currentCardId === cardId)
        },

        /**
         * Get the peer slot for a given slot.
         */
        peerOf: (state) => (slotId: string): SlotState | undefined => {
            const slot = state.slots[slotId]
            if (!slot?.peerSlotId) return undefined
            return state.slots[slot.peerSlotId]
        },
    },

    actions: {
        // ─── Span Negotiation Engine ────────────────────────

        /**
         * Negotiate span change between a slot and its peer.
         * Enforces minSpan/maxSpan constraints on both sides.
         * Total peer span is always preserved.
         *
         * @returns true if negotiation succeeded
         */
        negotiateSpan(slotId: string, newSpan: number): boolean {
            if (this.isNarrow) return false

            const slot = this.slots[slotId]
            if (!slot || !slot.peerSlotId) return false

            const peer = this.slots[slot.peerSlotId]
            if (!peer) return false

            // Calculate peer's new span (total must be preserved)
            const newPeerSpan = slot.totalPeerSpan - newSpan

            // Constraint enforcement
            if (newSpan < slot.minSpan || newSpan > slot.maxSpan) return false
            if (newPeerSpan < peer.minSpan || newPeerSpan > peer.maxSpan) return false

            // Integer enforcement
            const clampedSpan = Math.round(newSpan)
            const clampedPeer = slot.totalPeerSpan - clampedSpan

            // Final validation
            if (clampedSpan < slot.minSpan || clampedSpan > slot.maxSpan) return false
            if (clampedPeer < peer.minSpan || clampedPeer > peer.maxSpan) return false

            slot.span = clampedSpan
            peer.span = clampedPeer
            this.persistLayout()
            return true
        },

        // ─── Expansion Override Mode ────────────────────────

        /**
         * Toggle expansion for a card.
         * When expanded: card becomes col-span-12.
         * When collapsed: restore previous span state.
         *
         * Layout State Priority:
         * 1. Expansion override
         * 2. User-modified span state
         * 3. Default state
         */
        toggleExpand(cardId: string) {
            if (this.expandedCardId === cardId) {
                // Collapse — restore pre-expansion spans
                for (const [slotId, span] of Object.entries(this.preExpansionSpans)) {
                    if (this.slots[slotId]) {
                        this.slots[slotId].span = span
                    }
                }
                this.preExpansionSpans = {}
                this.expandedCardId = null
            } else {
                // Expand — store current spans for restoration
                const slot = this.getSlotByCardId(cardId)
                if (slot && slot.peerSlotId) {
                    const peer = this.slots[slot.peerSlotId]
                    this.preExpansionSpans = {
                        [slot.id]: slot.span,
                        ...(peer ? { [peer.id]: peer.span } : {}),
                    }
                }
                this.expandedCardId = cardId
            }
        },

        // ─── Card Slot Swapping ─────────────────────────────

        /**
         * Swap cards between two compatible slots.
         * Validates zone compatibility before swapping.
         *
         * @returns true if swap succeeded
         */
        swapCards(slotIdA: string, slotIdB: string): boolean {
            const a = this.slots[slotIdA]
            const b = this.slots[slotIdB]
            if (!a || !b) return false

            // Zone compatibility check
            if (a.zone !== b.zone) return false

            // Swap card assignments
            const tempCard = a.currentCardId
            a.currentCardId = b.currentCardId
            b.currentCardId = tempCard

            this.persistLayout()
            return true
        },

        // ─── KPI Isolation Layer ────────────────────────────

        /**
         * Update KPI card ordering.
         * KPIs are in a separate drag context — no cross-zone transfer.
         */
        updateKpiOrder(newOrder: string[]) {
            this.kpiOrder = newOrder
            this.persistLayout()
        },

        // ─── Responsive Mode ────────────────────────────────

        /**
         * Set responsive mode. Below desktop breakpoint,
         * span negotiation is disabled and all cards stack.
         */
        setNarrow(narrow: boolean) {
            this.isNarrow = narrow
        },

        // ─── Layout Reset ───────────────────────────────────

        /**
         * Reset all layout to defaults.
         * Clears persisted state.
         */
        resetLayout() {
            const defaults = createDefaultSlots()
            for (const [id, slot] of Object.entries(defaults)) {
                this.slots[id] = slot
            }
            this.kpiOrder = [...DEFAULT_KPI_ORDER]
            this.expandedCardId = null
            this.preExpansionSpans = {}

            try {
                localStorage.removeItem(STORAGE_KEY)
            } catch { /* ignore quota errors */ }
        },

        // ─── Persistence ────────────────────────────────────

        /**
         * Persist current layout to localStorage.
         */
        persistLayout() {
            try {
                const data: PersistedLayout = {
                    slots: {},
                    kpiOrder: this.kpiOrder,
                    version: LAYOUT_VERSION,
                }
                for (const [id, slot] of Object.entries(this.slots)) {
                    data.slots[id] = {
                        span: slot.span,
                        currentCardId: slot.currentCardId,
                    }
                }
                localStorage.setItem(STORAGE_KEY, JSON.stringify(data))
            } catch { /* ignore quota errors */ }
        },

        /**
         * Load persisted layout from localStorage.
         * Validates version and applies saved state on top of defaults.
         */
        loadLayout() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY)
                if (!raw) return

                const data: PersistedLayout = JSON.parse(raw)

                // Version check
                if (data.version !== LAYOUT_VERSION) {
                    localStorage.removeItem(STORAGE_KEY)
                    return
                }

                // Apply saved slot states
                if (data.slots) {
                    for (const [id, saved] of Object.entries(data.slots)) {
                        if (this.slots[id]) {
                            // Validate saved span against constraints
                            const slot = this.slots[id]
                            if (saved.span >= slot.minSpan && saved.span <= slot.maxSpan) {
                                slot.span = saved.span
                            }
                            slot.currentCardId = saved.currentCardId
                        }
                    }
                }

                // Apply KPI order
                if (data.kpiOrder?.length) {
                    this.kpiOrder = data.kpiOrder
                }
            } catch {
                // Corrupt data — remove
                try { localStorage.removeItem(STORAGE_KEY) } catch { /* ignore */ }
            }
        },
    },
})
