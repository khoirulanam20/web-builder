<template>
    <span
        :class="[
            'inline-flex items-center border-4 border-white px-3 py-1 text-xs font-black uppercase tracking-tight',
            variant === 'success' ? 'bg-green-500 text-black' : '',
            variant === 'warning' ? 'bg-yellow-400 text-black' : '',
            variant === 'danger' ? 'bg-red-500 text-white' : '',
            variant === 'info' ? 'bg-cyan-400 text-black' : '',
            variant === 'accent' ? 'bg-yellow-400 text-black' : '',
            variant === 'primary' ? 'bg-blue-500 text-white' : '',
            variant === 'default' ? 'bg-gray-800 text-white' : '',
            className
        ]"
        :style="{
            boxShadow: '4px 4px 0px 0px #ffffff',
        }"
    >
        <slot />
    </span>
</template>

<script setup>
defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'success', 'warning', 'danger', 'info', 'accent', 'primary'].includes(value),
    },
    className: {
        type: String,
        default: '',
    },
});
</script>
