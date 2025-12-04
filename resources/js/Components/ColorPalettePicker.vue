<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-700">
                Pilih Color Palette dari ColorHunt
            </label>
            <button
                type="button"
                @click="showPicker = !showPicker"
                class="text-xs text-indigo-600 hover:text-indigo-700"
            >
                {{ showPicker ? 'Sembunyikan' : 'Pilih Palette' }}
            </button>
        </div>

        <div v-if="showPicker" class="space-y-3">
            <!-- Search/Filter -->
            <div class="flex gap-2">
                <select
                    v-model="selectedCategory"
                    @change="filterPalettes"
                    class="flex-1 rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="all">Semua Kategori</option>
                    <option value="warm">Warm Colors</option>
                    <option value="cool">Cool Colors</option>
                    <option value="pastel">Pastel</option>
                    <option value="dark">Dark Theme</option>
                    <option value="bright">Bright & Vibrant</option>
                </select>
            </div>

            <!-- Palette Grid -->
            <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3">
                <div
                    v-for="(palette, index) in filteredPalettes"
                    :key="index"
                    @click="selectPalette(palette)"
                    :class="[
                        'cursor-pointer rounded-lg border-2 p-2 transition-all hover:shadow-md',
                        selectedPaletteIndex === index
                            ? 'border-indigo-500 bg-indigo-50'
                            : 'border-gray-200 hover:border-gray-300'
                    ]"
                >
                    <div class="flex gap-1">
                        <div
                            v-for="(color, colorIndex) in palette.colors"
                            :key="colorIndex"
                            :style="{ backgroundColor: color }"
                            class="h-12 flex-1 rounded"
                            :title="color"
                        ></div>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-gray-600">
                        <span>{{ palette.name }}</span>
                        <span class="font-mono text-[10px]">{{ palette.colors.join(', ') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Palette Preview -->
        <div v-if="selectedPalette" class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <div class="mb-2 text-xs font-medium text-gray-700">Palette Terpilih:</div>
            <div class="flex gap-1">
                <div
                    v-for="(color, index) in selectedPalette.colors"
                    :key="index"
                    :style="{ backgroundColor: color }"
                    class="h-10 flex-1 rounded"
                    :title="color"
                ></div>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs">
                <span class="font-medium text-gray-700">{{ selectedPalette.name }}</span>
                <button
                    type="button"
                    @click="clearSelection"
                    class="text-red-600 hover:text-red-700"
                >
                    Hapus
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const emit = defineEmits(['update:primary', 'update:secondary', 'update:accent']);

const showPicker = ref(false);
const selectedCategory = ref('all');
const selectedPaletteIndex = ref(null);
const selectedPalette = ref(null);

// Popular ColorHunt palettes (manually curated)
const palettes = ref([
    {
        name: 'Ocean Breeze',
        colors: ['#2E86AB', '#A23B72', '#F18F01', '#C73E1D'],
        category: 'cool'
    },
    {
        name: 'Sunset Warm',
        colors: ['#FF6B6B', '#FFE66D', '#4ECDC4', '#95E1D3'],
        category: 'warm'
    },
    {
        name: 'Pastel Dream',
        colors: ['#FFB6C1', '#FFE4E1', '#E0BBE4', '#957DAD'],
        category: 'pastel'
    },
    {
        name: 'Dark Elegant',
        colors: ['#2C3E50', '#34495E', '#7F8C8D', '#ECF0F1'],
        category: 'dark'
    },
    {
        name: 'Vibrant Energy',
        colors: ['#FF6B9D', '#C44569', '#F8B500', '#FF6B6B'],
        category: 'bright'
    },
    {
        name: 'Nature Green',
        colors: ['#2D5016', '#3E7C17', '#6A994E', '#A7C957'],
        category: 'cool'
    },
    {
        name: 'Royal Purple',
        colors: ['#6C5CE7', '#A29BFE', '#FD79A8', '#FDCB6E'],
        category: 'bright'
    },
    {
        name: 'Minimal Gray',
        colors: ['#2D3436', '#636E72', '#B2BEC3', '#DFE6E9'],
        category: 'dark'
    },
    {
        name: 'Coral Reef',
        colors: ['#FF7675', '#FD79A8', '#FDCB6E', '#55EFC4'],
        category: 'warm'
    },
    {
        name: 'Sky Blue',
        colors: ['#74B9FF', '#0984E3', '#00B894', '#00CEC9'],
        category: 'cool'
    },
    {
        name: 'Autumn Leaves',
        colors: ['#D63031', '#E17055', '#FDCB6E', '#6C5CE7'],
        category: 'warm'
    },
    {
        name: 'Mint Fresh',
        colors: ['#00B894', '#55EFC4', '#81ECEC', '#74B9FF'],
        category: 'cool'
    },
    {
        name: 'Rose Gold',
        colors: ['#E84393', '#FD79A8', '#FDCB6E', '#F39C12'],
        category: 'warm'
    },
    {
        name: 'Midnight Blue',
        colors: ['#2D3436', '#0984E3', '#6C5CE7', '#A29BFE'],
        category: 'dark'
    },
    {
        name: 'Peach Soft',
        colors: ['#FFE5D9', '#FFCAD4', '#F4ACB7', '#9D8189'],
        category: 'pastel'
    },
    {
        name: 'Electric Blue',
        colors: ['#0984E3', '#74B9FF', '#00CEC9', '#55EFC4'],
        category: 'bright'
    },
    {
        name: 'Lavender Mist',
        colors: ['#E0BBE4', '#957DAD', '#D291BC', '#FEC8D8'],
        category: 'pastel'
    },
    {
        name: 'Forest Deep',
        colors: ['#2D5016', '#3E7C17', '#6A994E', '#A7C957'],
        category: 'cool'
    },
    {
        name: 'Sunset Orange',
        colors: ['#F39C12', '#E67E22', '#D35400', '#E74C3C'],
        category: 'warm'
    },
    {
        name: 'Icy Blue',
        colors: ['#74B9FF', '#81ECEC', '#55EFC4', '#DFE6E9'],
        category: 'cool'
    }
]);

const filteredPalettes = computed(() => {
    if (selectedCategory.value === 'all') {
        return palettes.value;
    }
    return palettes.value.filter(p => p.category === selectedCategory.value);
});

const selectPalette = (palette) => {
    selectedPalette.value = palette;
    selectedPaletteIndex.value = palettes.value.findIndex(p => p.name === palette.name);
    
    // Update colors - assign to primary, secondary, accent
    if (palette.colors.length >= 3) {
        emit('update:primary', palette.colors[0]);
        emit('update:secondary', palette.colors[1]);
        emit('update:accent', palette.colors[2]);
    }
};

const clearSelection = () => {
    selectedPalette.value = null;
    selectedPaletteIndex.value = null;
    showPicker.value = false;
};

const filterPalettes = () => {
    // Filter is handled by computed property
};
</script>

