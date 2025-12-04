<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ColorPalettePicker from '@/Components/ColorPalettePicker.vue';
import SectionManager from '@/Components/SectionManager.vue';
import ProgressIndicator from '@/Components/ProgressIndicator.vue';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityInput from '@/Components/Acernity/Input.vue';
import AcernityTextarea from '@/Components/Acernity/Textarea.vue';
import AcernitySelect from '@/Components/Acernity/Select.vue';

const form = useForm({
    ai_provider: 'openrouter',
    prompt: '',
    website_name: '',
    description: '',
    target_audience: '',
    style_tone: '',
    icon_library: '',
    primary_color: '#3b82f6',
    secondary_color: '#8b5cf6',
    accent_color: '#f59e0b',
    reference_image: null,
    sections: ['navbar', 'hero', 'about', 'services', 'contact', 'footer'],
});

const imagePreview = ref(null);
const showProgress = ref(false);
const progressValue = ref(0);
const progressMessage = ref('Menggenerate website...');

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (!file.type.startsWith('image/')) {
            alert('File harus berupa gambar');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file maksimal 5MB');
            return;
        }

        form.reference_image = file;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    form.reference_image = null;
    imagePreview.value = null;
    const fileInput = document.getElementById('reference_image');
    if (fileInput) {
        fileInput.value = '';
    }
};

function submit() {
    showProgress.value = true;
    progressValue.value = 0;
    progressMessage.value = 'Mengirim request ke AI...';

    // Simulate progress
    const progressInterval = setInterval(() => {
        if (progressValue.value < 90) {
            progressValue.value += Math.random() * 10;
            if (progressValue.value >= 20 && progressValue.value < 50) {
                progressMessage.value = 'Menganalisa prompt dan membuat struktur...';
            } else if (progressValue.value >= 50 && progressValue.value < 80) {
                progressMessage.value = 'Menambahkan styling dan animasi...';
            } else if (progressValue.value >= 80) {
                progressMessage.value = 'Menyelesaikan website...';
            }
        }
    }, 500);

    form.post(route('projects.store'), {
        forceFormData: true,
        onSuccess: () => {
            clearInterval(progressInterval);
            progressValue.value = 100;
            setTimeout(() => {
                showProgress.value = false;
            }, 1000);
        },
        onError: () => {
            clearInterval(progressInterval);
            showProgress.value = false;
        },
        onFinish: () => {
            clearInterval(progressInterval);
        },
    });
}
</script>

<template>
    <Head title="Buat Website dari Prompt" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black text-white">
                        BUAT WEBSITE DARI PROMPT
                    </h2>
                    <p class="mt-1 text-sm font-bold text-gray-400">
                        GENERATE WEBSITE PROFESIONAL DENGAN AI DALAM HITUNGAN DETIK
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="route('projects.import-code')">
                        <AcernityButton variant="ghost">
                            MODE PASTE CODE
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
            <div class="mx-auto max-w-4xl px-6">
                <form
                    @submit.prevent="submit"
                    class="space-y-6"
                >
                    <!-- AI Provider Selection -->
                    <AcernityCard accent>
                        <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                            PILIH AI PROVIDER
                        </h3>
                        <AcernitySelect
                            id="ai_provider"
                            v-model="form.ai_provider"
                            label="Provider AI"
                            hint="Pilih provider AI yang akan digunakan untuk generate website"
                        >
                            <option value="openrouter">OpenRouter (Claude, GPT, dll)</option>
                            <option value="google_gemini">Google Gemini (Langsung)</option>
                        </AcernitySelect>
                        <p class="mt-2 text-xs font-bold text-gray-400">
                            <span v-if="form.ai_provider === 'openrouter'">
                                Menggunakan OpenRouter untuk akses berbagai model AI (Claude, GPT-4, dll). Perlu OPENROUTER_API_KEY.
                            </span>
                            <span v-else>
                                Menggunakan Google Gemini API langsung. Perlu GOOGLE_GEMINI_API_KEY dari Google AI Studio.
                            </span>
                        </p>
                    </AcernityCard>

                    <!-- Basic Information -->
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
                                hint="Nama brand atau website yang akan ditampilkan"
                            />

                            <AcernityTextarea
                                id="description"
                                v-model="form.description"
                                label="Deskripsi Singkat"
                                :rows="3"
                                placeholder="Contoh: Klinik modern dengan layanan kesehatan lengkap dan booking online 24/7"
                                hint="Deskripsi singkat tentang website/bisnis Anda"
                            />

                            <AcernityInput
                                id="target_audience"
                                v-model="form.target_audience"
                                label="Target Audiens"
                                placeholder="Contoh: Pasien usia 25-50 tahun, keluarga muda"
                                hint="Siapa target pengunjung website ini?"
                            />

                            <AcernitySelect
                                id="style_tone"
                                v-model="form.style_tone"
                                label="Style & Tone"
                                hint="Pilih gaya dan tone yang sesuai dengan brand Anda"
                            >
                                <option value="">Pilih style...</option>
                                <option value="modern">Modern & Minimalis</option>
                                <option value="professional">Profesional & Formal</option>
                                <option value="casual">Casual & Friendly</option>
                                <option value="creative">Creative & Bold</option>
                                <option value="elegant">Elegant & Luxurious</option>
                                <option value="tech">Tech & Futuristic</option>
                            </AcernitySelect>

                            <AcernitySelect
                                id="icon_library"
                                v-model="form.icon_library"
                                label="Icon Library"
                                hint="Pilih library icon utama yang ingin digunakan di desain"
                            >
                                <option value="">Auto / Bebas (Biarkan AI memilih)</option>
                                <option value="fontawesome">Font Awesome</option>
                                <option value="heroicons">Heroicons</option>
                                <option value="phosphor">Phosphor Icons</option>
                                <option value="lucide">Lucide Icons</option>
                            </AcernitySelect>
                        </div>
                    </AcernityCard>

                    <!-- Color Palette -->
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

                    <!-- Section Manager -->
                    <AcernityCard accent>
                        <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                            ATUR SECTION LANDING PAGE
                        </h3>
                        <SectionManager v-model="form.sections" />
                        <p class="mt-3 text-xs font-bold text-gray-400">
                            Section yang dipilih akan digunakan oleh AI untuk generate website sesuai urutan yang ditentukan.
                        </p>
                    </AcernityCard>

                    <!-- Reference Image -->
                    <AcernityCard accent>
                        <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                            GAMBAR REFERENSI (OPSIONAL)
                        </h3>
                        <div class="space-y-4">
                            <label
                                for="reference_image"
                                class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-white/10"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                Upload Gambar Referensi
                            </label>
                            <input
                                id="reference_image"
                                type="file"
                                accept="image/*"
                                @change="handleImageUpload"
                                class="hidden"
                            />
                            <p class="text-xs font-bold text-gray-400">
                                Upload gambar sebagai referensi desain (maks. 5MB).
                                <span class="font-black text-yellow-400">AI AKAN MENGANALISA GAMBAR INI</span> untuk menyesuaikan layout, warna, dan gaya visual website.
                            </p>
                            <div
                                v-if="imagePreview"
                                class="relative inline-block"
                            >
                                <img
                                    :src="imagePreview"
                                    alt="Preview"
                                    class="max-h-64 rounded-lg border border-white/10"
                                />
                                <button
                                    type="button"
                                    @click="removeImage"
                                    class="absolute right-2 top-2 rounded-full bg-red-500/80 p-2 text-white backdrop-blur-sm transition-colors hover:bg-red-500"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </AcernityCard>

                    <!-- Prompt -->
                    <AcernityCard accent>
                        <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                            PROMPT DETAIL (OPSIONAL)
                        </h3>
                        <AcernityTextarea
                            id="prompt"
                            v-model="form.prompt"
                            :rows="6"
                            placeholder="Tambahkan detail tambahan seperti: section yang diinginkan (hero, layanan, portfolio, testimoni, FAQ, kontak), fitur khusus, animasi, dll."
                            hint="Detail tambahan untuk hasil yang lebih spesifik. Informasi di atas akan otomatis digunakan."
                            :error="form.errors.prompt"
                        />
                    </AcernityCard>

                    <!-- Actions -->
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
                            {{ form.processing ? 'MENGGENERATE...' : '✨ GENERATE WEBSITE' }}
                        </AcernityButton>
                    </div>
                </form>

                <!-- Example Prompts -->
                <AcernityCard accent class="mt-6">
                    <p class="mb-3 text-xs font-black uppercase tracking-wider text-yellow-400">
                        CONTOH PROMPT
                    </p>
                    <ul class="space-y-2 text-sm font-bold text-white">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-yellow-400 font-black">•</span>
                            <span>"Buat landingpage software house modern dengan hero, layanan, portfolio, dan CTA WhatsApp."</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-cyan-400 font-black">•</span>
                            <span>"Company profile bengkel motor warna hitam kuning."</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 text-red-500 font-black">•</span>
                            <span>"Portfolio developer dengan animasi scroll ringan."</span>
                        </li>
                    </ul>
                </AcernityCard>
            </div>
        </div>

        <!-- Progress Indicator -->
        <ProgressIndicator
            :show="showProgress"
            :progress="progressValue"
            :message="progressMessage"
        />
    </AuthenticatedLayout>
</template>
