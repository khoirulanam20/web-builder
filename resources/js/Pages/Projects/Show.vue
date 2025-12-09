<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { showConfirm } from '@/plugins/swal';
import { router } from '@inertiajs/vue3';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityBadge from '@/Components/Acernity/Badge.vue';
import AcernityTextarea from '@/Components/Acernity/Textarea.vue';
import AcernitySelect from '@/Components/Acernity/Select.vue';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    html: {
        type: String,
        default: null,
    },
    css: {
        type: String,
        default: null,
    },
});

const activeTab = ref('preview');
const copied = ref(false);
const editingTab = ref(null);
const showImprove = ref(false);

const editForm = useForm({
    html: props.html || '',
    css: props.css || '',
});

const improveForm = useForm({
    improve_prompt: '',
    ai_provider: 'openrouter',
});

const jsCode = computed(() => {
    if (!props.html) {
        return '';
    }

    const scriptRegex = /<script\b[^>]*>([\s\S]*?)<\/script>/gi;
    const parts = [];
    let match;

    while ((match = scriptRegex.exec(props.html)) !== null) {
        const content = (match[1] || '').trim();
        if (content) {
            parts.push(content);
        }
    }

    return parts.join('\n\n// --- SCRIPT SEPARATOR ---\n\n');
});

const copyToClipboard = async (text, type) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = type;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const startEditing = (tab) => {
    editingTab.value = tab;
    if (tab === 'html') {
        editForm.html = props.html || '';
    } else if (tab === 'css') {
        editForm.css = props.css || '';
    }
};

const saveCode = () => {
    editForm.put(route('projects.update-code', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingTab.value = null;
            window.location.reload();
        },
    });
};

const cancelEdit = () => {
    editingTab.value = null;
    editForm.reset();
};

const submitImprove = () => {
    if (!improveForm.improve_prompt.trim()) {
        window.showToast('Silakan isi instruksi perbaikan terlebih dahulu', 'warning');
        return;
    }

    showConfirm({
        title: 'Improve Website?',
        text: 'Hanya bagian spesifik yang diminta akan diubah (font, warna, teks, bentuk). Struktur website tetap sama. Proses ini mungkin memakan waktu beberapa saat.',
        icon: 'question',
        confirmText: 'Ya, Improve',
        confirmColor: '#8b5cf6',
    }).then((result) => {
        if (result.isConfirmed) {
            improveForm.post(route('projects.improve', props.project.id), {
                preserveScroll: true,
                onSuccess: () => {
                    showImprove.value = false;
                    improveForm.reset();
                    window.location.reload();
                },
            });
        }
    });
};

const handlePublish = () => {
    showConfirm({
        title: 'Publish Project?',
        text: 'Project akan dipublikasikan dan dapat diakses secara publik. Apakah Anda yakin?',
        icon: 'question',
        confirmText: 'Ya, Publish',
        confirmColor: '#10b981',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('projects.publish', props.project.id), {
                onSuccess: () => {
                    // Toast akan muncul otomatis
                },
            });
        }
    });
};

const handleRepublish = () => {
    showConfirm({
        title: 'Republish Project?',
        text: 'Project akan dipublish ulang dan file di server akan diupdate dengan versi terbaru. Lanjutkan?',
        icon: 'question',
        confirmText: 'Ya, Republish',
        confirmColor: '#3b82f6',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('projects.publish', props.project.id), {
                onSuccess: () => {
                    // Toast akan muncul otomatis
                },
            });
        }
    });
};
</script>

<template>
    <Head :title="`Project - ${project.slug}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black text-white">
                        DETAIL PROJECT
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm font-bold text-gray-400 line-clamp-2">
                        {{ project.prompt }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <AcernityButton
                        variant="accent"
                        @click="showImprove = true"
                    >
                        ✨ IMPROVE
                    </AcernityButton>
                    <AcernityButton
                        v-if="project.status === 'draft'"
                        variant="success"
                        @click="handlePublish"
                    >
                        PUBLISH
                    </AcernityButton>
                    <Link :href="route('projects.index')">
                        <AcernityButton variant="ghost">
                            ← KEMBALI
                        </AcernityButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 bg-black">
            <div class="mx-auto max-w-7xl space-y-6 px-6">
                <!-- Main Content (Preview & Code Tabs) -->
                <div class="space-y-4">
                        <AcernityCard>
                            <!-- Tabs -->
                            <div class="border-b-4 border-white">
                                <nav
                                    class="-mb-1 flex space-x-2"
                                    aria-label="Tabs"
                                >
                                    <button
                                        @click="activeTab = 'preview'"
                                        :class="[
                                            activeTab === 'preview'
                                                ? 'border-4 border-yellow-400 bg-yellow-400 text-black'
                                                : 'border-4 border-white bg-black text-white hover:bg-white hover:text-black',
                                            'whitespace-nowrap px-4 py-2 text-sm font-black uppercase transition-all',
                                        ]"
                                        style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                    >
                                        PREVIEW
                                    </button>
                                    <button
                                        @click="activeTab = 'html'"
                                        :class="[
                                            activeTab === 'html'
                                                ? 'border-4 border-yellow-400 bg-yellow-400 text-black'
                                                : 'border-4 border-white bg-black text-white hover:bg-white hover:text-black',
                                            'whitespace-nowrap px-4 py-2 text-sm font-black uppercase transition-all',
                                        ]"
                                        style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                    >
                                        HTML
                                    </button>
                                    <button
                                        v-if="css"
                                        @click="activeTab = 'css'"
                                        :class="[
                                            activeTab === 'css'
                                                ? 'border-4 border-yellow-400 bg-yellow-400 text-black'
                                                : 'border-4 border-white bg-black text-white hover:bg-white hover:text-black',
                                            'whitespace-nowrap px-4 py-2 text-sm font-black uppercase transition-all',
                                        ]"
                                        style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                    >
                                        CSS
                                    </button>
                                    <button
                                        v-if="jsCode"
                                        @click="activeTab = 'js'"
                                        :class="[
                                            activeTab === 'js'
                                                ? 'border-4 border-yellow-400 bg-yellow-400 text-black'
                                                : 'border-4 border-white bg-black text-white hover:bg-white hover:text-black',
                                            'whitespace-nowrap px-4 py-2 text-sm font-black uppercase transition-all',
                                        ]"
                                        style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                    >
                                        JS
                                    </button>
                                </nav>
                            </div>

                            <!-- Tab Content -->
                            <div class="mt-4">
                                <!-- Preview Tab -->
                                <div
                                    v-if="activeTab === 'preview'"
                                    class="border-4 border-white bg-black p-2"
                                    style="box-shadow: 4px 4px 0px 0px #ffffff;"
                                >
                                    <div
                                        v-if="html"
                                        class="relative w-full"
                                        style="padding-top: 56.25%; box-shadow: 2px 2px 0px 0px #ffffff;"
                                    >
                                        <iframe
                                            :src="route('projects.preview', project.id)"
                                            class="absolute inset-0 h-full w-full border-4 border-white bg-white"
                                            sandbox="allow-scripts allow-forms"
                                            referrerpolicy="no-referrer"
                                        ></iframe>
                                    </div>
                                    <div
                                        v-else
                                        class="flex h-64 items-center justify-center border-4 border-white bg-black text-sm font-black text-white"
                                        style="box-shadow: 4px 4px 0px 0px #ffffff;"
                                    >
                                        BELUM ADA KONTEN HTML
                                    </div>
                                </div>

                                <!-- HTML Tab -->
                                <div
                                    v-if="activeTab === 'html'"
                                    class="relative"
                                >
                                    <div class="absolute right-2 top-2 z-10 flex gap-2">
                                        <AcernityButton
                                            v-if="editingTab !== 'html'"
                                            variant="accent"
                                            @click="startEditing('html')"
                                            class="text-xs"
                                        >
                                            EDIT
                                        </AcernityButton>
                                        <AcernityButton
                                            variant="primary"
                                            @click="copyToClipboard(html, 'html')"
                                            class="text-xs"
                                        >
                                            {{ copied === 'html' ? '✓ COPIED!' : 'COPY' }}
                                        </AcernityButton>
                                    </div>
                                    <div v-if="editingTab === 'html'">
                                        <div class="sticky top-0 z-20 mb-2 flex items-center justify-between border-4 border-white bg-black p-3" style="box-shadow: 4px 4px 0px 0px #ffffff;">
                                            <span class="text-xs font-black uppercase text-yellow-400">
                                                MODE EDIT - HTML
                                            </span>
                                            <div class="flex gap-2">
                                                <AcernityButton
                                                    variant="ghost"
                                                    @click="cancelEdit"
                                                    class="text-xs"
                                                >
                                                    BATAL
                                                </AcernityButton>
                                                <AcernityButton
                                                    variant="accent"
                                                    @click="saveCode"
                                                    :processing="editForm.processing"
                                                    class="text-xs"
                                                >
                                                    💾 SIMPAN
                                                </AcernityButton>
                                            </div>
                                        </div>
                                        <textarea
                                            v-model="editForm.html"
                                            class="w-full h-[600px] border-4 border-white bg-black p-4 text-xs font-black text-white font-mono resize-none focus:border-yellow-400 focus:outline-none"
                                            style="box-shadow: 4px 4px 0px 0px #ffffff;"
                                        ></textarea>
                                    </div>
                                    <pre
                                        v-else
                                        class="max-h-[600px] overflow-auto border-4 border-white bg-black p-4 text-xs font-mono text-white"
                                        style="box-shadow: 4px 4px 0px 0px #ffffff;"
                                    ><code>{{ html }}</code></pre>
                                </div>

                                <!-- CSS Tab -->
                                <div
                                    v-if="activeTab === 'css' && css"
                                    class="relative"
                                >
                                    <div class="absolute right-2 top-2 z-10 flex gap-2">
                                        <AcernityButton
                                            v-if="editingTab !== 'css'"
                                            variant="accent"
                                            @click="startEditing('css')"
                                            class="text-xs"
                                        >
                                            EDIT
                                        </AcernityButton>
                                        <AcernityButton
                                            variant="primary"
                                            @click="copyToClipboard(css, 'css')"
                                            class="text-xs"
                                        >
                                            {{ copied === 'css' ? '✓ COPIED!' : 'COPY' }}
                                        </AcernityButton>
                                    </div>
                                    <div v-if="editingTab === 'css'">
                                        <div class="sticky top-0 z-20 mb-2 flex items-center justify-between border-4 border-white bg-black p-3" style="box-shadow: 4px 4px 0px 0px #ffffff;">
                                            <span class="text-xs font-black uppercase text-yellow-400">
                                                MODE EDIT - CSS
                                            </span>
                                            <div class="flex gap-2">
                                                <AcernityButton
                                                    variant="ghost"
                                                    @click="cancelEdit"
                                                    class="text-xs"
                                                >
                                                    BATAL
                                                </AcernityButton>
                                                <AcernityButton
                                                    variant="accent"
                                                    @click="saveCode"
                                                    :processing="editForm.processing"
                                                    class="text-xs"
                                                >
                                                    💾 SIMPAN
                                                </AcernityButton>
                                            </div>
                                        </div>
                                        <textarea
                                            v-model="editForm.css"
                                            class="w-full h-[600px] border-4 border-white bg-black p-4 text-xs font-black text-white font-mono resize-none focus:border-yellow-400 focus:outline-none"
                                            style="box-shadow: 4px 4px 0px 0px #ffffff;"
                                        ></textarea>
                                    </div>
                                    <pre
                                        v-else
                                        class="max-h-[600px] overflow-auto border-4 border-white bg-black p-4 text-xs font-mono text-white"
                                        style="box-shadow: 4px 4px 0px 0px #ffffff;"
                                    ><code>{{ css }}</code></pre>
                                </div>

                                <!-- JS Tab -->
                                <div
                                    v-if="activeTab === 'js'"
                                    class="relative"
                                >
                                    <div class="absolute right-2 top-2 z-10 flex gap-2">
                                        <AcernityButton
                                            variant="primary"
                                            @click="copyToClipboard(jsCode || html, 'js')"
                                            class="text-xs"
                                        >
                                            {{ copied === 'js' ? '✓ COPIED!' : 'COPY JS' }}
                                        </AcernityButton>
                                    </div>
                                    <pre
                                        class="max-h-[600px] overflow-auto border-4 border-white bg-black p-4 text-xs font-mono text-white"
                                        style="box-shadow: 4px 4px 0px 0px #ffffff;"
                                    ><code>{{ jsCode || '// JavaScript sudah tertanam di dalam file HTML. Edit langsung di tab HTML jika ingin mengubah.' }}</code></pre>
                                </div>
                            </div>
                        </AcernityCard>
                </div>

                <!-- Info Project di bawah preview -->
                <div class="space-y-4">
                    <AcernityCard accent>
                        <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                            INFO PROJECT
                        </h3>
                        <dl class="space-y-4 text-sm">
                            <div>
                                <dt class="mb-1 font-black text-white">
                                    STATUS
                                </dt>
                                <dd class="flex items-center gap-2">
                                    <AcernityBadge
                                        :variant="project.status === 'published' ? 'success' : 'warning'"
                                    >
                                        {{ project.status }}
                                    </AcernityBadge>
                                    <AcernityButton
                                        v-if="project.status === 'published'"
                                        variant="primary"
                                        class="text-xs"
                                        @click="handleRepublish"
                                    >
                                        REPUBLISH
                                    </AcernityButton>
                                    <AcernityButton
                                        v-else
                                        variant="success"
                                        class="text-xs"
                                        @click="handlePublish"
                                    >
                                        PUBLISH
                                    </AcernityButton>
                                </dd>
                            </div>
                            <div v-if="project.preview_url">
                                <dt class="mb-1 font-black text-white">
                                    PREVIEW URL
                                </dt>
                                <dd class="flex items-center gap-2 text-xs font-bold">
                                    <a
                                        :href="project.preview_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="break-all text-cyan-400 underline decoration-dotted underline-offset-2 hover:text-cyan-300"
                                    >
                                        {{ project.preview_url }}
                                    </a>
                                    <AcernityButton
                                        variant="primary"
                                        class="text-[10px] px-2 py-1"
                                        @click="copyToClipboard(project.preview_url, 'preview_url')"
                                    >
                                        {{ copied === 'preview_url' ? '✓ COPIED' : 'COPY' }}
                                    </AcernityButton>
                                </dd>
                            </div>
                            <div>
                                <dt class="mb-1 font-black text-white">
                                    PROMPT
                                </dt>
                                <dd class="whitespace-pre-wrap text-xs font-bold text-gray-400">
                                    {{ project.prompt }}
                                </dd>
                            </div>
                        </dl>
                    </AcernityCard>
                </div>
            </div>
        </div>

        <!-- Improve Modal -->
        <div
            v-if="showImprove"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
            @click.self="showImprove = false"
        >
            <AcernityCard
                accent
                class="w-full max-w-2xl"
            >
                <div class="mb-4">
                    <h3 class="text-xl font-black uppercase text-white">
                        IMPROVE WEBSITE
                    </h3>
                    <p class="mt-1 text-sm font-bold text-gray-400">
                        Jelaskan perubahan spesifik yang ingin dilakukan (font, warna, teks, bentuk). Struktur website tidak akan berubah.
                    </p>
                </div>
                <form
                    @submit.prevent="submitImprove"
                    class="space-y-4"
                >
                    <!-- AI Provider Selection -->
                    <AcernitySelect
                        id="ai_provider"
                        v-model="improveForm.ai_provider"
                        label="Provider AI"
                        hint="Pilih provider AI yang akan digunakan untuk improve website"
                        :error="improveForm.errors.ai_provider"
                    >
                        <option value="openrouter">OpenRouter (Claude, GPT, dll)</option>
                        <option value="google_gemini">Google Gemini (Langsung)</option>
                    </AcernitySelect>
                    <p class="text-xs font-bold text-gray-400 -mt-2">
                        <span v-if="improveForm.ai_provider === 'openrouter'">
                            Menggunakan OpenRouter untuk akses berbagai model AI. Jika rate limit, coba pilih Google Gemini.
                        </span>
                        <span v-else>
                            Menggunakan Google Gemini API langsung. Alternatif jika OpenRouter rate limit.
                        </span>
                    </p>
                    <AcernityTextarea
                        id="improve_prompt"
                        v-model="improveForm.improve_prompt"
                        :rows="6"
                        placeholder="Contoh: Ubah warna header menjadi biru, Ganti font menjadi Arial, Ubah teks 'Selamat Datang' menjadi 'Welcome', Buat tombol lebih bulat, Ubah warna background section hero menjadi gradient biru, dll."
                        :error="improveForm.errors.improve_prompt"
                    />
                    <div class="flex justify-end gap-3">
                        <AcernityButton
                            variant="ghost"
                            @click="showImprove = false"
                        >
                            BATAL
                        </AcernityButton>
                        <AcernityButton
                            type="submit"
                            variant="accent"
                            :processing="improveForm.processing"
                        >
                            ✨ IMPROVE WEBSITE
                        </AcernityButton>
                    </div>
                </form>
            </AcernityCard>
        </div>
    </AuthenticatedLayout>
</template>
