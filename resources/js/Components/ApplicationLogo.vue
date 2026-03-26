<template>
    <img 
        src="/images/logo.png" 
        alt="Warehouse Management Logo" 
        class="warehouse-logo"
    />
</template>

<style scoped>
.warehouse-logo {
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.2));
    transition: all 0.3s ease;
    object-fit: contain;
}

.warehouse-logo:hover {
    filter: drop-shadow(0 8px 12px rgba(255, 107, 53, 0.4));
    transform: scale(1.05);
}
</style>
