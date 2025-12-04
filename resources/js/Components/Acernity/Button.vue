<template>
    <button
        :type="type"
        :disabled="disabled || processing"
        :class="[
            'group relative inline-flex items-center justify-center border-4 border-white px-6 py-3 font-black text-sm uppercase tracking-tight transition-all duration-200',
            variant === 'primary' ? 'bg-black text-white brutal-shadow brutal-hover' : '',
            variant === 'secondary' ? 'bg-gray-800 text-white brutal-shadow brutal-hover' : '',
            variant === 'danger' ? 'bg-black border-red-500 text-red-500 brutal-shadow-accent brutal-hover-accent' : '',
            variant === 'success' ? 'bg-black border-green-500 text-green-500 brutal-shadow-accent brutal-hover-accent' : '',
            variant === 'ghost' ? 'bg-transparent border-white/50 text-white hover:border-white hover:bg-white/10' : '',
            variant === 'accent' ? 'bg-yellow-400 border-yellow-400 text-black font-black brutal-shadow-accent brutal-hover-accent' : '',
            variant === 'cyan' ? 'bg-cyan-400 border-cyan-400 text-black font-black brutal-shadow-cyan brutal-hover-cyan' : '',
            disabled || processing ? 'opacity-50 cursor-not-allowed' : '',
            className
        ]"
        @click="$emit('click')"
    >
        <span class="relative z-10 flex items-center gap-2">
            <slot />
        </span>
    </button>
</template>

<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'danger', 'success', 'ghost', 'accent', 'cyan'].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    processing: {
        type: Boolean,
        default: false,
    },
    className: {
        type: String,
        default: '',
    },
});

defineEmits(['click']);
</script>
