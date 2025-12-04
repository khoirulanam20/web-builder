<template>
    <div class="relative">
        <label
            v-if="label"
            :for="id"
            class="mb-2 block text-sm font-black uppercase tracking-tight text-white"
        >
            {{ label }}
        </label>
        <div class="relative">
            <select
                :id="id"
                :value="modelValue"
                :disabled="disabled"
                :class="[
                    'w-full appearance-none border-4 border-white bg-black px-4 py-3 font-bold text-white focus:border-yellow-400 focus:outline-none transition-all duration-200 cursor-pointer',
                    error ? 'border-red-500 focus:border-red-500' : '',
                    disabled ? 'opacity-50 cursor-not-allowed' : '',
                    className
                ]"
                :style="{
                    boxShadow: error ? '4px 4px 0px 0px #ff0000' : '4px 4px 0px 0px #ffffff',
                }"
                @change="$emit('update:modelValue', $event.target.value)"
            >
                <slot />
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                <svg
                    class="h-5 w-5 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="3"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </div>
        </div>
        <p
            v-if="error"
            class="mt-1 text-xs font-bold uppercase text-red-500"
        >
            {{ error }}
        </p>
        <p
            v-else-if="hint"
            class="mt-1 text-xs font-bold text-gray-400"
        >
            {{ hint }}
        </p>
    </div>
</template>

<script setup>
defineProps({
    id: String,
    modelValue: [String, Number],
    label: String,
    hint: String,
    error: String,
    disabled: {
        type: Boolean,
        default: false,
    },
    className: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue']);
</script>
