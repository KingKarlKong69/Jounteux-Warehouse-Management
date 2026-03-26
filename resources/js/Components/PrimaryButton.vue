<script setup lang="ts">
import { ref } from 'vue';

const ripples = ref<Array<{ x: number; y: number; id: number }>>([]);
let rippleId = 0;

const createRipple = (event: MouseEvent) => {
    const button = event.currentTarget as HTMLButtonElement;
    const rect = button.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    
    ripples.value.push({ x, y, id: rippleId++ });
    
    setTimeout(() => {
        ripples.value.shift();
    }, 600);
};
</script>

<template>
    <button
        @click="createRipple"
        class="relative inline-flex items-center rounded-lg border-2 border-warehouse-orange bg-warehouse-orange px-6 py-3 text-sm font-bold uppercase tracking-widest text-white shadow-lg transition-all duration-300 ease-in-out overflow-hidden
        hover:bg-warehouse-rust hover:border-warehouse-rust hover:shadow-warehouse-orange/50 hover:shadow-2xl hover:-translate-y-1
        focus:outline-none focus:ring-4 focus:ring-warehouse-orange/40 
        active:translate-y-0 active:shadow-md
        disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0"
    >
        <!-- Ripple Effects -->
        <span
            v-for="ripple in ripples"
            :key="ripple.id"
            class="absolute rounded-full bg-white/40 pointer-events-none animate-ripple"
            :style="{
                left: `${ripple.x}px`,
                top: `${ripple.y}px`,
                width: '10px',
                height: '10px',
                marginLeft: '-5px',
                marginTop: '-5px',
            }"
        ></span>

        <!-- Shine Effect on Hover -->
        <span class="absolute inset-0 overflow-hidden rounded-lg">
            <span class="shine-effect"></span>
        </span>

        <!-- Button Content -->
        <span class="relative z-10">
            <slot />
        </span>
    </button>
</template>

<style scoped>
@keyframes ripple {
    0% {
        transform: scale(0);
        opacity: 1;
    }
    100% {
        transform: scale(30);
        opacity: 0;
    }
}

.animate-ripple {
    animation: ripple 0.6s ease-out;
}

/* Shine effect */
.shine-effect {
    position: absolute;
    top: -50%;
    left: -100%;
    width: 50%;
    height: 200%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    transform: skewX(-25deg);
    transition: left 0.5s ease;
}

button:hover .shine-effect {
    left: 200%;
}

/* Pulsing glow animation for non-disabled state */
button:not(:disabled) {
    animation: subtle-glow 3s ease-in-out infinite;
}

@keyframes subtle-glow {
    0%, 100% {
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
    }
    50% {
        box-shadow: 0 4px 25px rgba(255, 107, 53, 0.5);
    }
}

/* Enhanced focus state */
button:focus {
    transform: scale(1.02);
}

/* Enhanced active state */
button:active:not(:disabled) {
    transform: scale(0.98);
}
</style>
