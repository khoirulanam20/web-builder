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
            <textarea
                :id="id"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :rows="rows"
                :class="[
                    'w-full border-4 border-white bg-black px-4 py-3 font-bold text-white placeholder:text-gray-500 focus:border-yellow-400 focus:outline-none transition-all duration-200 resize-none',
                    error ? 'border-red-500 focus:border-red-500' : '',
                    disabled ? 'opacity-50 cursor-not-allowed' : '',
                    className
                ]"
                :style="{
                    boxShadow: error ? '4px 4px 0px 0px #ff0000' : '4px 4px 0px 0px #ffffff',
                }"
                @input="$emit('update:modelValue', $event.target.value)"
                @blur="$emit('blur')"
                @focus="$emit('focus')"
            ></textarea>
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
    placeholder: String,
    hint: String,
    error: String,
    disabled: {
        type: Boolean,
        default: false,
    },
    rows: {
        type: Number,
        default: 3,
    },
    className: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue', 'blur', 'focus']);
</script>
