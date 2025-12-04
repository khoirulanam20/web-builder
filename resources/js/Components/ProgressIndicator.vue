<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    progress: {
        type: Number,
        default: 0,
    },
    message: {
        type: String,
        default: 'Menggenerate website...',
    },
});

const animatedProgress = ref(0);

onMounted(() => {
    if (props.show) {
        const interval = setInterval(() => {
            if (animatedProgress.value < props.progress) {
                animatedProgress.value = Math.min(animatedProgress.value + 2, props.progress);
            } else {
                clearInterval(interval);
            }
        }, 50);
    }
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm"
    >
        <div
            class="border-4 border-white bg-black p-8"
            style="box-shadow: 8px 8px 0px 0px #ffff00;"
        >
            <div class="mb-6 text-center">
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center border-4 border-yellow-400 bg-yellow-400"
                    style="box-shadow: 4px 4px 0px 0px #ffffff;"
                >
                    <svg
                        class="h-8 w-8 animate-spin text-black"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                </div>
                <h3 class="mb-2 text-xl font-black uppercase text-white">
                    GENERATING WEBSITE
                </h3>
                <p class="text-sm font-bold text-gray-400">
                    {{ message }}
                </p>
            </div>

            <!-- Progress Bar -->
            <div
                class="relative h-8 border-4 border-white bg-black"
                style="box-shadow: 4px 4px 0px 0px #ffffff;"
            >
                <div
                    class="absolute inset-0 flex items-center justify-center border-4 border-yellow-400 bg-yellow-400 transition-all duration-300"
                    :style="{
                        width: `${animatedProgress}%`,
                        boxShadow: animatedProgress >= 100 ? '4px 4px 0px 0px #ffffff' : 'none',
                    }"
                >
                    <span
                        v-if="animatedProgress > 10"
                        class="text-sm font-black text-black"
                    >
                        {{ Math.round(animatedProgress) }}%
                    </span>
                </div>
            </div>

            <!-- Steps -->
            <div class="mt-6 space-y-2 text-xs font-bold text-gray-400">
                <div
                    :class="[
                        animatedProgress >= 20 ? 'text-yellow-400' : '',
                        'flex items-center gap-2',
                    ]"
                >
                    <span
                        v-if="animatedProgress >= 20"
                        class="text-yellow-400"
                    >
                        ✓
                    </span>
                    <span v-else>○</span>
                    <span>Menganalisa prompt...</span>
                </div>
                <div
                    :class="[
                        animatedProgress >= 50 ? 'text-yellow-400' : '',
                        'flex items-center gap-2',
                    ]"
                >
                    <span
                        v-if="animatedProgress >= 50"
                        class="text-yellow-400"
                    >
                        ✓
                    </span>
                    <span v-else>○</span>
                    <span>Membuat struktur HTML...</span>
                </div>
                <div
                    :class="[
                        animatedProgress >= 80 ? 'text-yellow-400' : '',
                        'flex items-center gap-2',
                    ]"
                >
                    <span
                        v-if="animatedProgress >= 80"
                        class="text-yellow-400"
                    >
                        ✓
                    </span>
                    <span v-else>○</span>
                    <span>Menambahkan styling & animasi...</span>
                </div>
                <div
                    :class="[
                        animatedProgress >= 100 ? 'text-yellow-400' : '',
                        'flex items-center gap-2',
                    ]"
                >
                    <span
                        v-if="animatedProgress >= 100"
                        class="text-yellow-400"
                    >
                        ✓
                    </span>
                    <span v-else>○</span>
                    <span>Menyelesaikan website...</span>
                </div>
            </div>
        </div>
    </div>
</template>

