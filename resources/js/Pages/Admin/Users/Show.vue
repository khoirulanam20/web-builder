<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityBadge from '@/Components/Acernity/Badge.vue';

defineProps({
    user: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="`Detail User - ${user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black text-white">
                        DETAIL PENGGUNA
                    </h2>
                    <p class="mt-1 text-sm font-bold text-gray-400">
                        {{ user.name }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="route('admin.users.edit', user.id)">
                        <AcernityButton variant="accent">
                            EDIT USER
                        </AcernityButton>
                    </Link>
                    <Link :href="route('admin.users.index')">
                        <AcernityButton variant="ghost">
                            ← KEMBALI
                        </AcernityButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-black min-h-screen">
            <div class="mx-auto max-w-4xl px-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- User Info -->
                    <div class="lg:col-span-1">
                        <AcernityCard accent>
                            <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                                INFORMASI PENGGUNA
                            </h3>
                            <dl class="space-y-4 text-sm">
                                <div>
                                    <dt class="mb-1 font-black text-white">
                                        NAMA
                                    </dt>
                                    <dd class="text-xs font-bold text-gray-400">
                                        {{ user.name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="mb-1 font-black text-white">
                                        EMAIL
                                    </dt>
                                    <dd class="text-xs font-bold text-gray-400">
                                        {{ user.email }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="mb-1 font-black text-white">
                                        ROLE
                                    </dt>
                                    <dd>
                                        <AcernityBadge
                                            :variant="user.role === 'admin' ? 'accent' : 'primary'"
                                        >
                                            {{ user.role }}
                                        </AcernityBadge>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="mb-1 font-black text-white">
                                        DIBUAT
                                    </dt>
                                    <dd class="text-xs font-bold text-gray-400">
                                        {{ new Date(user.created_at).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'long',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        }) }}
                                    </dd>
                                </div>
                            </dl>
                        </AcernityCard>
                    </div>

                    <!-- User Projects -->
                    <div class="lg:col-span-2">
                        <AcernityCard accent>
                            <h3 class="mb-4 text-sm font-black uppercase tracking-wider text-yellow-400">
                                PROJECT PENGGUNA ({{ user.projects_count || 0 }})
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="project in user.projects"
                                    :key="project.id"
                                    class="border-4 border-white bg-black p-3"
                                    style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="line-clamp-2 text-sm font-black text-white">
                                                {{ project.prompt || 'Untitled Project' }}
                                            </p>
                                            <p class="mt-1 text-xs font-bold text-gray-400">
                                                {{ new Date(project.created_at).toLocaleDateString('id-ID') }}
                                            </p>
                                        </div>
                                        <AcernityBadge
                                            :variant="project.status === 'published' ? 'success' : 'warning'"
                                            class="ml-2 shrink-0"
                                        >
                                            {{ project.status }}
                                        </AcernityBadge>
                                    </div>
                                </div>
                                <div
                                    v-if="!user.projects || user.projects.length === 0"
                                    class="py-8 text-center text-sm font-bold text-gray-400"
                                >
                                    User ini belum memiliki project
                                </div>
                            </div>
                        </AcernityCard>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

