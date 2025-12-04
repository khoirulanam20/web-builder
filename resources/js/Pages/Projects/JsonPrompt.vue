<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityInput from '@/Components/Acernity/Input.vue';
import AcernityTextarea from '@/Components/Acernity/Textarea.vue';
import AcernitySelect from '@/Components/Acernity/Select.vue';
import ColorPalettePicker from '@/Components/ColorPalettePicker.vue';
import SectionManager from '@/Components/SectionManager.vue';

const form = useForm({
    website_name: '',
    description: '',
    target_audience: '',
    style_tone: '',
    primary_color: '#3b82f6',
    secondary_color: '#8b5cf6',
    accent_color: '#f59e0b',
    sections: ['navbar', 'hero', 'about', 'services', 'contact', 'footer'],
    prompt: '',
});

const jsonResult = computed(() => {
    const data = {
        website_name: form.website_name || null,
        description: form.description || null,
        target_audience: form.target_audience || null,
        style_tone: form.style_tone || null,
        colors: {
            primary: form.primary_color || null,
            secondary: form.secondary_color || null,
            accent: form.accent_color || null,
        },
        sections: form.sections || [],
        extra_prompt: form.prompt || null,
    };

    return JSON.stringify(data, null, 2);
});

const copyJson = async () => {
    try {
        await navigator.clipboard.writeText(jsonResult.value);
        window.showToast?.('JSON prompt berhasil disalin ke clipboard', 'success');
    } catch (e) {
        console.error(e);
        alert('Gagal menyalin JSON ke clipboard');
    }
};
</script>

<template>
    <Head title="Generate JSON Prompt Website" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black text-white">
                        GENERATE JSON PROMPT WEBSITE
                    </h2>
                    <p class="mt-1 text-sm font-bold text-gray-400">
                        ISI FORM SEPERTI MODE PROMPT, HASILNYA KAMI JADIKAN <span class="font-black text-yellow-400">JSON CODE</span> TANPA MEMANGGIL AI
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="route('projects.create')">
                        <AcernityButton variant="ghost">
                            MODE PROMPT (AI)
                        </AcernityButton>
                    </Link>
                    <Link :href="route('projects.index')">
                        <AcernityButton variant="ghost">
                            ← KEMBALI
                        </AcernityButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-black py-8">
            <div class="mx-auto max-w-6xl px-6 space-y-6">
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Form Input -->
                    <div class="space-y-6">
                        <AcernityCard accent>
                            <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                                INFORMASI DASAR
                            </h3>
                            <div class="space-y-4">
                                <AcernityInput
                                    id="website_name"
                                    v-model="form.website_name"
                                    label="Nama Website/Brand"
                                    placeholder="Contoh: Klinik Sehat Sentosa"
                                />
                                <AcernityTextarea
                                    id="description"
                                    v-model="form.description"
                                    label="Deskripsi Singkat"
                                    :rows="3"
                                    placeholder="Contoh: Klinik modern dengan layanan kesehatan lengkap dan booking online 24/7"
                                />
                                <AcernityInput
                                    id="target_audience"
                                    v-model="form.target_audience"
                                    label="Target Audiens"
                                    placeholder="Contoh: Pasien usia 25-50 tahun, keluarga muda"
                                />
                                <AcernitySelect
                                    id="style_tone"
                                    v-model="form.style_tone"
                                    label="Style & Tone"
                                >
                                    <option value="">Pilih style...</option>
                                    <option value="modern">Modern & Minimalis</option>
                                    <option value="professional">Profesional & Formal</option>
                                    <option value="casual">Casual & Friendly</option>
                                    <option value="creative">Creative & Bold</option>
                                    <option value="elegant">Elegant & Luxurious</option>
                                    <option value="tech">Tech & Futuristic</option>
                                </AcernitySelect>
                            </div>
                        </AcernityCard>

                        <AcernityCard accent>
                            <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                                COLOR PALETTE
                            </h3>
                            <ColorPalettePicker
                                @update:primary="form.primary_color = $event"
                                @update:secondary="form.secondary_color = $event"
                                @update:accent="form.accent_color = $event"
                            />
                            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                <div class="space-y-2">
                                    <label
                                        for="primary_color"
                                        class="block text-sm font-medium text-gray-300"
                                    >
                                        Primary Color
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            id="primary_color"
                                            v-model="form.primary_color"
                                            type="color"
                                            class="h-10 w-20 cursor-pointer rounded-lg border border-white/10 bg-white/5"
                                        />
                                        <input
                                            v-model="form.primary_color"
                                            type="text"
                                            class="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                            placeholder="#3b82f6"
                                        />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label
                                        for="secondary_color"
                                        class="block text-sm font-medium text-gray-300"
                                    >
                                        Secondary Color
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            id="secondary_color"
                                            v-model="form.secondary_color"
                                            type="color"
                                            class="h-10 w-20 cursor-pointer rounded-lg border border-white/10 bg-white/5"
                                        />
                                        <input
                                            v-model="form.secondary_color"
                                            type="text"
                                            class="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                            placeholder="#8b5cf6"
                                        />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label
                                        for="accent_color"
                                        class="block text-sm font-medium text-gray-300"
                                    >
                                        Accent Color
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            id="accent_color"
                                            v-model="form.accent_color"
                                            type="color"
                                            class="h-10 w-20 cursor-pointer rounded-lg border border-white/10 bg-white/5"
                                        />
                                        <input
                                            v-model="form.accent_color"
                                            type="text"
                                            class="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                            placeholder="#f59e0b"
                                        />
                                    </div>
                                </div>
                            </div>
                        </AcernityCard>

                        <AcernityCard accent>
                            <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                                ATUR SECTION LANDING PAGE
                            </h3>
                            <SectionManager v-model="form.sections" />
                        </AcernityCard>

                        <AcernityCard accent>
                            <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                                PROMPT TAMBAHAN (OPSIONAL)
                            </h3>
                            <AcernityTextarea
                                id="prompt"
                                v-model="form.prompt"
                                :rows="5"
                                placeholder="Tambahkan detail tambahan seperti: section yang diinginkan, fitur khusus, animasi, tone copywriting, dll."
                            />
                        </AcernityCard>
                    </div>

                    <!-- JSON Output -->
                    <div class="space-y-4">
                        <AcernityCard accent>
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-black uppercase tracking-wider text-yellow-400">
                                    HASIL JSON PROMPT
                                </h3>
                                <AcernityButton
                                    variant="primary"
                                    class="text-xs"
                                    @click="copyJson"
                                >
                                    COPY JSON
                                </AcernityButton>
                            </div>
                            <pre class="max-h-[700px] overflow-auto border-4 border-white bg-black p-4 text-xs font-mono text-green-400" style="box-shadow: 4px 4px 0px 0px #ffffff;">
{{ jsonResult }}
                            </pre>
                        </AcernityCard>

                        <AcernityCard accent>
                            <p class="mb-2 text-xs font-black uppercase tracking-wider text-yellow-400">
                                CARA PAKAI
                            </p>
                            <ul class="space-y-2 text-xs font-bold text-gray-400">
                                <li>- Gunakan JSON ini sebagai input untuk API / sistem lain yang akan generate website.</li>
                                <li>- Field sudah disusun rapi: informasi dasar, warna, section, dan prompt tambahan.</li>
                                <li>- Tidak ada panggilan AI di fitur ini, murni utilitas pembuat struktur JSON.</li>
                            </ul>
                        </AcernityCard>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


