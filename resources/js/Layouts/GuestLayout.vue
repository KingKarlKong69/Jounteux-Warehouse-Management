<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen relative overflow-hidden animated-gradient-bg flex items-center justify-center px-4 py-8">
        <!-- Floating Geometric Shapes (Construction Elements) -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Floating Shape 1 - Hexagon (Steel Bolt) -->
            <div class="floating-shape" style="top: 10%; left: 10%; width: 80px; height: 80px;">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="w-full h-full fill-white/10">
                    <polygon points="50 1 95 25 95 75 50 99 5 75 5 25" stroke="white" stroke-width="2" fill="none" />
                </svg>
            </div>
            
            <!-- Floating Shape 2 - Rectangle (Steel Beam) -->
            <div class="floating-shape" style="top: 60%; right: 15%; width: 100px; height: 40px;">
                <svg viewBox="0 0 100 40" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                    <rect x="2" y="2" width="96" height="36" stroke="white" stroke-width="2" fill="none" opacity="0.2" />
                    <line x1="20" y1="2" x2="20" y2="38" stroke="white" stroke-width="1" opacity="0.2" />
                    <line x1="50" y1="2" x2="50" y2="38" stroke="white" stroke-width="1" opacity="0.2" />
                    <line x1="80" y1="2" x2="80" y2="38" stroke="white" stroke-width="1" opacity="0.2" />
                </svg>
            </div>
            
            <!-- Floating Shape 3 - Triangle (Warning) -->
            <div class="floating-shape" style="bottom: 15%; left: 20%; width: 70px; height: 70px;">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                    <polygon points="50 10, 90 90, 10 90" stroke="white" stroke-width="2" fill="none" opacity="0.15" />
                </svg>
            </div>

            <!-- Floating Shape 4 - Gear -->
            <div class="floating-shape" style="top: 30%; right: 25%; width: 60px; height: 60px;">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="w-full h-full fill-white/10">
                    <circle cx="50" cy="50" r="20" stroke="white" stroke-width="2" fill="none" opacity="0.2" />
                    <circle cx="50" cy="50" r="10" fill="white" opacity="0.1" />
                </svg>
            </div>

            <!-- Floating Shape 5 - Rectangle (Blueprint) -->
            <div class="floating-shape" style="bottom: 25%; right: 10%; width: 90px; height: 60px;">
                <svg viewBox="0 0 90 60" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                    <rect x="2" y="2" width="86" height="56" stroke="white" stroke-width="1" fill="none" opacity="0.15" />
                    <line x1="2" y1="15" x2="88" y2="15" stroke="white" stroke-width="0.5" opacity="0.15" />
                    <line x1="2" y1="30" x2="88" y2="30" stroke="white" stroke-width="0.5" opacity="0.15" />
                    <line x1="2" y1="45" x2="88" y2="45" stroke="white" stroke-width="0.5" opacity="0.15" />
                </svg>
            </div>
        </div>

        <!-- Construction Pattern Overlay -->
        <div class="absolute inset-0 construction-pattern pointer-events-none"></div>

        <!-- Main Content Container -->
        <div class="relative z-10 w-full max-w-md animate-slide-up">
            <!-- Logo Section -->
            <div class="flex justify-center mb-8">
                <Link href="/" class="group">
                    <div class="relative p-4 glass-panel rounded-2xl hover-glow transition-all duration-300">
                        <ApplicationLogo class="h-20 w-20 fill-current text-warehouse-orange drop-shadow-lg" />
                        <!-- Glow effect on hover -->
                        <div class="absolute inset-0 rounded-2xl bg-warehouse-orange/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                </Link>
            </div>

            <!-- Login Form Container with Glass Morphism -->
            <div class="glass-panel rounded-2xl shadow-2xl p-8 transition-all duration-300 hover:shadow-warehouse-orange/20">
                <slot />
            </div>

            <!-- Footer Text -->
            <div class="mt-6 text-center">
                <p class="text-white/80 text-sm text-shadow-sm">
                     Warehouse Management System
                </p>
                <p class="text-white/60 text-xs mt-1">
                    Built for Modern Construction & Logistics
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Additional component-specific animations */
@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(5deg);
    }
}

/* Enhance the glass panel hover effect */
.glass-panel:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px 0 rgba(255, 107, 53, 0.25);
}
</style>
