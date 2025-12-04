<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityInput from '@/Components/Acernity/Input.vue';
import AcernityTextarea from '@/Components/Acernity/Textarea.vue';
import AcernitySelect from '@/Components/Acernity/Select.vue';

const form = useForm({
    website_name: '',
    description: '',
    icon_library: '',
    html_code: '',
    css_code: '',
    js_code: '',
});

const submit = () => {
    form.post(route('projects.import-code.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Buat Website dari Code" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black text-white">
                        BUAT WEBSITE DARI CODE
                    </h2>
                    <p class="mt-1 text-sm font-bold text-gray-400">
                        PASTE HTML, CSS, DAN JS ANDA – KAMI SIMPAN MENJADI SATU FILE <span class="font-black text-yellow-400">INDEX.HTML</span> YANG BISA DI IMPROVE &amp; PUBLISH
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
            <div class="mx-auto max-w-5xl px-6 space-y-6">
                <form
                    @submit.prevent="submit"
                    class="space-y-6"
                >
                    <AcernityCard accent>
                        <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                            INFORMASI PROJECT
                        </h3>
                        <div class="space-y-4">
                            <AcernityInput
                                id="website_name"
                                v-model="form.website_name"
                                label="Nama Website/Project"
                                placeholder="Contoh: Landingpage Klinik Sehat Sentosa"
                            />
                            <AcernityTextarea
                                id="description"
                                v-model="form.description"
                                label="Deskripsi Singkat (digunakan sebagai prompt dasar untuk fitur Improve)"
                                :rows="3"
                                placeholder="Contoh: Landingpage klinik modern dengan fokus booking online, testimoni, dan informasi layanan."
                            />

                            <AcernitySelect
                                id="icon_library"
                                v-model="form.icon_library"
                                label="Icon Library"
                                hint="Informasi ini akan digunakan saat fitur Improve (agar konsisten dengan library icon pilihan Anda)"
                            >
                                <option value="">Tidak spesifik / bebas</option>
                                <option value="fontawesome">Font Awesome</option>
                                <option value="heroicons">Heroicons</option>
                                <option value="phosphor">Phosphor Icons</option>
                                <option value="lucide">Lucide Icons</option>
                            </AcernitySelect>
                        </div>
                    </AcernityCard>

                    <AcernityCard accent>
                        <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                            PASTE KODE ANDA
                        </h3>
                        <p class="mb-4 text-xs font-bold text-gray-400">
                            <span class="font-black text-yellow-400">CATATAN:</span>
                            Semua JavaScript akan ditanam langsung di dalam file <code class="rounded bg-white/10 px-1 py-0.5">index.html</code>.
                            Fitur <span class="font-black">IMPROVE</span> dan <span class="font-black">PUBLISH</span> akan tetap berfungsi seperti project lain.
                        </p>
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="space-y-3 lg:col-span-2">
                                <AcernityTextarea
                                    id="html_code"
                                    v-model="form.html_code"
                                    label="HTML"
                                    :rows="10"
                                    placeholder="Tempelkan kode HTML lengkap atau potongan body di sini..."
                                    :error="form.errors.html_code"
                                />
                            </div>
                            <div class="space-y-3">
                                <AcernityTextarea
                                    id="css_code"
                                    v-model="form.css_code"
                                    label="CSS"
                                    :rows="10"
                                    placeholder="Tempelkan CSS (opsional). Akan di-embed sebagai &lt;style&gt; di dalam &lt;head&gt;."
                                />
                            </div>
                            <div class="space-y-3">
                                <AcernityTextarea
                                    id="js_code"
                                    v-model="form.js_code"
                                    label="JavaScript"
                                    :rows="10"
                                    placeholder="Tempelkan JavaScript (opsional). Akan di-embed sebagai &lt;script&gt; sebelum &lt;/body&gt;."
                                />
                            </div>
                        </div>
                    </AcernityCard>

                    <div class="flex items-center justify-end gap-4">
                        <Link :href="route('projects.index')">
                            <AcernityButton variant="ghost">
                                Batal
                            </AcernityButton>
                        </Link>
                        <AcernityButton
                            type="submit"
                            variant="accent"
                            :processing="form.processing"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'MENYIMPAN...' : '💾 SIMPAN DARI CODE' }}
                        </AcernityButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


