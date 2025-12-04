<template>
    <div
        :class="[
            'group relative border-4 border-white bg-black p-6 transition-all duration-200',
            hover ? 'brutal-hover' : '',
            className
        ]"
        :style="{
            boxShadow: glow ? (accent ? '4px 4px 0px 0px #ffff00' : '4px 4px 0px 0px #ffffff') : 'none',
        }"
    >
        <div class="relative z-10">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    glow: {
        type: Boolean,
        default: true,
    },
    hover: {
        type: Boolean,
        default: true,
    },
    accent: {
        type: Boolean,
        default: false,
    },
    className: {
        type: String,
        default: '',
    },
});
</script>
