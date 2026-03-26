<script setup lang="ts">
import { onMounted, ref } from 'vue';

const model = defineModel<string>({ required: true });

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        class="rounded-lg shadow-sm transition-all duration-300 ease-in-out focus:ring-2 focus:ring-warehouse-orange/50 focus:shadow-lg"
        v-model="model"
        ref="input"
    />
</template>

<style scoped>
input {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

input:focus {
    transform: translateY(-1px);
}

input:hover:not(:focus) {
    border-color: rgba(255, 107, 53, 0.3);
}
</style>
