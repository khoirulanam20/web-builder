<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { showConfirm } from '@/plugins/swal';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityBadge from '@/Components/Acernity/Badge.vue';

defineProps({
    projects: {
        type: Array,
        required: true,
    },
});

const handleDelete = (projectId, projectPrompt) => {
    showConfirm({
        title: 'Hapus Project?',
        text: `Apakah Anda yakin ingin menghapus project "${projectPrompt.substring(0, 50)}..."? Tindakan ini tidak dapat dibatalkan.`,
        icon: 'warning',
        confirmText: 'Ya, Hapus',
        confirmColor: '#ef4444',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('projects.destroy', projectId), {
                onSuccess: () => {
                    // Toast akan muncul otomatis dari flash message
                },
            });
        }
    });
};

const handlePublish = (projectId) => {
    showConfirm({
        title: 'Publish Project?',
        text: 'Project akan dipublikasikan dan dapat diakses secara publik. Apakah Anda yakin?',
        icon: 'question',
        confirmText: 'Ya, Publish',
        confirmColor: '#10b981',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('projects.publish', projectId), {
                onSuccess: () => {
                    // Toast akan muncul otomatis dari flash message
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Project Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black text-white">
                        PROJECT GENERATOR
                    </h2>
                    <p class="mt-1 text-sm font-bold text-gray-400">
                        KELOLA SEMUA PROJECT WEBSITE ANDA
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="route('projects.create')">
                        <AcernityButton variant="accent">
                            + BUAT WEBSITE BARU
                        </AcernityButton>
                    </Link>
                    <Link :href="route('projects.json-prompt')">
                        <AcernityButton variant="ghost">
                            GENERATE JSON PROMPT
                        </AcernityButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-black min-h-screen">
            <div class="mx-auto max-w-7xl px-6">
                <div
                    v-if="projects.length === 0"
                    class="flex flex-col items-center justify-center gap-6 py-20 text-center"
                >
                    <div class="border-4 border-white bg-yellow-400 p-6" style="box-shadow: 4px 4px 0px 0px #ffffff;">
                        <svg
                            class="h-16 w-16 text-black"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="3"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4m14 0V9a2 2 0 00-2-2H7a2 2 0 00-2 2v2"
                            />
                        </svg>
                    </div>
                    <div class="space-y-2">
                        <p class="text-3xl font-black text-white">
                            BELUM ADA PROJECT
                        </p>
                        <p class="max-w-md text-sm font-bold text-gray-400">
                            Mulai dengan memasukkan prompt seperti
                            "Buat landing page jasa interior design warna krem dengan hero besar dan CTA tombol WhatsApp."
                        </p>
                    </div>
                    <Link :href="route('projects.create')">
                        <AcernityButton variant="accent">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="3"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            BUAT PROJECT PERTAMA
                        </AcernityButton>
                    </Link>
                </div>

                <div v-else>
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-black text-white">
                                DAFTAR PROJECT
                            </h3>
                            <p class="mt-1 text-sm font-bold text-gray-400">
                                TOTAL {{ projects.length }} PROJECT
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <AcernityCard
                            v-for="project in projects"
                            :key="project.id"
                            class="group overflow-hidden"
                        >
                            <!-- Preview Thumbnail -->
                            <div class="relative -mx-6 -mt-6 mb-4 h-48 overflow-hidden border-b-4 border-white bg-gray-900">
                                <iframe
                                    v-if="project.id"
                                    :src="route('projects.preview', project.id)"
                                    class="h-full w-full scale-105 transform transition-transform duration-300 group-hover:scale-100"
                                    sandbox="allow-scripts allow-forms"
                                    referrerpolicy="no-referrer"
                                ></iframe>
                                <div
                                    v-else
                                    class="flex h-full items-center justify-center"
                                >
                                    <svg
                                        class="h-16 w-16 text-slate-500"
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
                                </div>
                                <!-- Status Badge -->
                                <div class="absolute right-3 top-3">
                                    <AcernityBadge
                                        :variant="project.status === 'published' ? 'success' : 'warning'"
                                    >
                                        {{ project.status === 'published' ? 'Published' : 'Draft' }}
                                    </AcernityBadge>
                                </div>
                            </div>

                            <!-- Card Content -->
                            <div class="space-y-4">
                                <h4 class="line-clamp-2 text-sm font-black text-white">
                                    {{ project.prompt || 'UNTITLED PROJECT' }}
                                </h4>
                                
                                <div class="flex items-center gap-2 text-xs font-bold text-gray-400">
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
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <span>{{ new Date(project.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        :href="route('projects.show', project.id)"
                                        class="flex-1"
                                    >
                                        <AcernityButton
                                            variant="primary"
                                            class="w-full text-xs font-black"
                                        >
                                            LIHAT DETAIL
                                        </AcernityButton>
                                    </Link>
                                    <button
                                        v-if="project.status === 'draft'"
                                        @click="handlePublish(project.id)"
                                        class="rounded-lg bg-emerald-500/20 p-2 text-emerald-400 transition-colors hover:bg-emerald-500/30"
                                        title="Publish"
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
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="handleDelete(project.id, project.prompt || 'Untitled Project')"
                                        class="rounded-lg bg-red-500/20 p-2 text-red-400 transition-colors hover:bg-red-500/30"
                                        title="Hapus"
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
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </AcernityCard>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
