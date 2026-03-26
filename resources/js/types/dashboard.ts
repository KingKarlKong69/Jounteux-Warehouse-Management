// ─── Dashboard Layout Type Definitions ──────────────────────
// Slot-governed adaptive analytics dashboard types

/**
 * Zone classification for layout isolation.
 * Each zone enforces its own drag/resize rules.
 */
export type SlotZone =
    | 'hero'
    | 'kpi'
    | 'analytics'
    | 'metric-stack'
    | 'operational'

/**
 * Slot type defines what kind of card can occupy a slot.
 * Used for drag compatibility validation.
 */
export type SlotType =
    | 'primary-chart'
    | 'secondary-chart'
    | 'operational-chart'
    | 'kpi'
    | 'metric-stack'

/**
 * Reactive slot state managed by the layout store.
 */
export interface SlotState {
    id: string
    currentCardId: string
    span: number
    defaultSpan: number
    minSpan: number
    maxSpan: number
    zone: SlotZone
    allowedTypes: SlotType[]
    peerSlotId: string | null
    /** Total span shared between this slot and its peer */
    totalPeerSpan: number
}

/**
 * Persisted layout structure for localStorage.
 */
export interface PersistedLayout {
    slots: Record<string, { span: number; currentCardId: string }>
    kpiOrder: string[]
    version: number
}

/**
 * Card definition for the component registry.
 */
export interface CardDefinition {
    id: string
    title: string
    subtitle: string
    iconColor: string
    navigateTo?: string
    slotType: SlotType
}

/**
 * KPI metric definition.
 */
export interface KpiDefinition {
    id: string
    label: string
    key: string
    color: string
    format: 'currency' | 'number'
    href?: string
    adminOnly?: boolean
}
