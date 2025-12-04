<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => ['navbar', 'hero', 'about', 'services', 'contact', 'footer'],
    },
});

const emit = defineEmits(['update:modelValue']);

const availableSections = [
    { id: 'navbar', label: 'Navbar', required: true },
    { id: 'hero', label: 'Hero Section', required: true },
    { id: 'about', label: 'About Us' },
    { id: 'services', label: 'Services' },
    { id: 'features', label: 'Features' },
    { id: 'portfolio', label: 'Portfolio' },
    { id: 'testimonials', label: 'Testimonials' },
    { id: 'pricing', label: 'Pricing' },
    { id: 'team', label: 'Team' },
    { id: 'gallery', label: 'Gallery' },
    { id: 'faq', label: 'FAQ' },
    { id: 'blog', label: 'Blog' },
    { id: 'contact', label: 'Contact', required: true },
    { id: 'footer', label: 'Footer', required: true },
];

const sections = ref([...props.modelValue]);
const draggingIndex = ref(null);

// Sync dengan props
watch(() => props.modelValue, (newValue) => {
    sections.value = [...newValue];
}, { deep: true });

const moveUp = (index) => {
    if (index > 0) {
        const temp = sections.value[index];
        sections.value[index] = sections.value[index - 1];
        sections.value[index - 1] = temp;
        emit('update:modelValue', [...sections.value]);
    }
};

const moveDown = (index) => {
    if (index < sections.value.length - 1) {
        const temp = sections.value[index];
        sections.value[index] = sections.value[index + 1];
        sections.value[index + 1] = temp;
        emit('update:modelValue', [...sections.value]);
    }
};

const removeSection = (index) => {
    const sectionId = sections.value[index];
    const section = availableSections.find(s => s.id === sectionId);
    if (section && !section.required) {
        sections.value.splice(index, 1);
        emit('update:modelValue', [...sections.value]);
    }
};

const addSection = (sectionId) => {
    if (!sections.value.includes(sectionId)) {
        sections.value.push(sectionId);
        emit('update:modelValue', [...sections.value]);
    }
};

const getSectionInfo = (sectionId) => {
    return availableSections.find(s => s.id === sectionId);
};

const availableToAdd = computed(() => {
    return availableSections.filter(s => !sections.value.includes(s.id));
});

const onDragStart = (event, index) => {
    draggingIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
};

const onDragOver = (event, index) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
};

const onDrop = (event, index) => {
    event.preventDefault();
    if (draggingIndex.value === null || draggingIndex.value === index) {
        return;
    }

    const updated = [...sections.value];
    const [moved] = updated.splice(draggingIndex.value, 1);
    updated.splice(index, 0, moved);

    sections.value = updated;
    draggingIndex.value = null;
    emit('update:modelValue', [...sections.value]);
};

const onDragEnd = () => {
    draggingIndex.value = null;
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-black uppercase tracking-tight text-white">
                ATUR SECTION LANDING PAGE
            </h4>
            <p class="text-xs font-bold text-gray-400">
                Drag atau gunakan tombol untuk mengatur urutan
            </p>
        </div>

        <!-- Current Sections -->
        <div class="space-y-2">
            <div
                v-for="(sectionId, index) in sections"
                :key="sectionId"
                class="group flex items-center gap-2 border-4 border-white bg-black p-3 transition-all hover:bg-gray-900 cursor-move"
                style="box-shadow: 2px 2px 0px 0px #ffffff;"
                draggable="true"
                @dragstart="onDragStart($event, index)"
                @dragover="onDragOver($event, index)"
                @drop="onDrop($event, index)"
                @dragend="onDragEnd"
            >
                <div class="flex h-8 w-8 shrink-0 items-center justify-center border-4 border-white bg-yellow-400 font-black text-black" style="box-shadow: 2px 2px 0px 0px #ffffff;">
                    <span class="text-xs">{{ index + 1 }}</span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-black uppercase text-white">
                        {{ getSectionInfo(sectionId)?.label || sectionId }}
                    </p>
                    <p
                        v-if="getSectionInfo(sectionId)?.required"
                        class="text-xs font-bold text-yellow-400"
                    >
                        Required
                    </p>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        @click="moveUp(index)"
                        :disabled="index === 0"
                        class="border-4 border-white bg-black px-2 py-1 text-xs font-black text-white transition-all hover:bg-white hover:text-black disabled:opacity-30 disabled:cursor-not-allowed"
                        style="box-shadow: 2px 2px 0px 0px #ffffff;"
                    >
                        ↑
                    </button>
                    <button
                        type="button"
                        @click="moveDown(index)"
                        :disabled="index === sections.length - 1"
                        class="border-4 border-white bg-black px-2 py-1 text-xs font-black text-white transition-all hover:bg-white hover:text-black disabled:opacity-30 disabled:cursor-not-allowed"
                        style="box-shadow: 2px 2px 0px 0px #ffffff;"
                    >
                        ↓
                    </button>
                    <button
                        v-if="!getSectionInfo(sectionId)?.required"
                        type="button"
                        @click="removeSection(index)"
                        class="border-4 border-red-500 bg-black px-2 py-1 text-xs font-black text-red-500 transition-all hover:bg-red-500 hover:text-white"
                        style="box-shadow: 2px 2px 0px 0px #ff0000;"
                    >
                        ×
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Section -->
        <div
            v-if="availableToAdd.length > 0"
            class="border-4 border-white bg-black p-4"
            style="box-shadow: 2px 2px 0px 0px #ffffff;"
        >
            <p class="mb-3 text-xs font-black uppercase text-yellow-400">
                TAMBAH SECTION
            </p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="section in availableToAdd"
                    :key="section.id"
                    type="button"
                    @click="addSection(section.id)"
                    class="border-4 border-white bg-black px-3 py-2 text-xs font-black text-white transition-all hover:bg-white hover:text-black"
                    style="box-shadow: 2px 2px 0px 0px #ffffff;"
                >
                    + {{ section.label }}
                </button>
            </div>
        </div>
    </div>
</template>

