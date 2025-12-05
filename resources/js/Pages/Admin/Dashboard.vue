<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityBadge from '@/Components/Acernity/Badge.vue';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black text-white">
                        🎛️ ADMIN DASHBOARD
                    </h2>
                    <p class="mt-1 text-sm font-bold text-gray-400">
                        PUSAT KENDALI APLIKASI WEB BUILDER
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="route('admin.users.index')">
                        <AcernityButton variant="accent">
                            👥 KELOLA PENGGUNA
                        </AcernityButton>
                    </Link>
                    <Link :href="route('dashboard')">
                        <AcernityButton variant="ghost">
                            ← KEMBALI KE DASHBOARD
                        </AcernityButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-black min-h-screen">
            <div class="mx-auto max-w-7xl px-6 space-y-6">
                <!-- Quick Stats -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Users -->
                    <AcernityCard accent>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-xs font-black uppercase tracking-wider text-gray-400">
                                    TOTAL PENGGUNA
                                </p>
                                <p class="mt-2 text-4xl font-black text-white">
                                    {{ stats.total_users || 0 }}
                                </p>
                                <p class="mt-1 text-xs font-bold text-gray-400">
                                    Terdaftar
                                </p>
                            </div>
                            <div class="border-4 border-white bg-yellow-400 p-4" style="box-shadow: 4px 4px 0px 0px #ffffff;">
                                <svg class="h-10 w-10 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                    </AcernityCard>

                    <!-- Total Projects -->
                    <AcernityCard accent>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-xs font-black uppercase tracking-wider text-gray-400">
                                    TOTAL PROJECT
                                </p>
                                <p class="mt-2 text-4xl font-black text-white">
                                    {{ stats.total_projects || 0 }}
                                </p>
                                <p class="mt-1 text-xs font-bold text-gray-400">
                                    Website dibuat
                                </p>
                            </div>
                            <div class="border-4 border-white bg-cyan-400 p-4" style="box-shadow: 4px 4px 0px 0px #ffffff;">
                                <svg class="h-10 w-10 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4m14 0V9a2 2 0 00-2-2H7a2 2 0 00-2 2v2" />
                                </svg>
                            </div>
                        </div>
                    </AcernityCard>

                    <!-- Published Projects -->
                    <AcernityCard accent>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-xs font-black uppercase tracking-wider text-gray-400">
                                    PUBLISHED
                                </p>
                                <p class="mt-2 text-4xl font-black text-white">
                                    {{ stats.published_projects || 0 }}
                                </p>
                                <p class="mt-1 text-xs font-bold text-gray-400">
                                    Website aktif
                                </p>
                            </div>
                            <div class="border-4 border-white bg-emerald-400 p-4" style="box-shadow: 4px 4px 0px 0px #ffffff;">
                                <svg class="h-10 w-10 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </AcernityCard>

                    <!-- Draft Projects -->
                    <AcernityCard accent>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-xs font-black uppercase tracking-wider text-gray-400">
                                    DRAFT
                                </p>
                                <p class="mt-2 text-4xl font-black text-white">
                                    {{ stats.draft_projects || 0 }}
                                </p>
                                <p class="mt-1 text-xs font-bold text-gray-400">
                                    Belum dipublish
                                </p>
                            </div>
                            <div class="border-4 border-white bg-amber-400 p-4" style="box-shadow: 4px 4px 0px 0px #ffffff;">
                                <svg class="h-10 w-10 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                        </div>
                    </AcernityCard>
                </div>

                <!-- Main Content Grid -->
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Recent Users -->
                    <div class="lg:col-span-2">
                        <AcernityCard accent>
                            <div class="mb-6 flex items-center justify-between">
                                <h3 class="text-lg font-black uppercase tracking-wider text-yellow-400">
                                    👥 PENGGUNA TERBARU
                                </h3>
                                <Link :href="route('admin.users.index')">
                                    <AcernityButton variant="ghost" class="text-xs">
                                        LIHAT SEMUA →
                                    </AcernityButton>
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <div
                                    v-for="user in stats.recent_users"
                                    :key="user.id"
                                    class="group flex items-center justify-between border-4 border-white bg-black p-4 transition-all hover:bg-white hover:text-black"
                                    style="box-shadow: 4px 4px 0px 0px #ffffff;"
                                >
                                    <div class="flex items-center gap-4 flex-1">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center border-4 border-white bg-yellow-400 font-black text-black" style="box-shadow: 2px 2px 0px 0px #ffffff;">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-black text-white group-hover:text-black">
                                                {{ user.name }}
                                            </p>
                                            <p class="text-xs font-bold text-gray-400 group-hover:text-gray-600 truncate">
                                                {{ user.email }}
                                            </p>
                                            <p class="mt-1 text-xs font-bold text-gray-400 group-hover:text-gray-600">
                                                Bergabung {{ new Date(user.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <AcernityBadge
                                            :variant="user.role === 'admin' ? 'accent' : 'primary'"
                                        >
                                            {{ user.role.toUpperCase() }}
                                        </AcernityBadge>
                                    </div>
                                </div>
                                <div
                                    v-if="!stats.recent_users || stats.recent_users.length === 0"
                                    class="py-12 text-center"
                                >
                                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center border-4 border-white bg-gray-800">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-400">
                                        Belum ada pengguna terdaftar
                                    </p>
                                </div>
                            </div>
                        </AcernityCard>
                    </div>

                    <!-- Quick Actions -->
                    <div>
                        <AcernityCard accent>
                            <h3 class="mb-4 text-lg font-black uppercase tracking-wider text-yellow-400">
                                ⚡ QUICK ACTIONS
                            </h3>
                            <div class="space-y-3">
                                <Link :href="route('admin.users.create')" class="block">
                                    <div class="group border-4 border-white bg-black p-4 text-center transition-all hover:bg-white" style="box-shadow: 4px 4px 0px 0px #ffffff;">
                                        <p class="text-sm font-black text-white group-hover:text-black">
                                            ➕ TAMBAH USER BARU
                                        </p>
                                    </div>
                                </Link>
                                <Link :href="route('admin.users.index')" class="block">
                                    <div class="group border-4 border-white bg-black p-4 text-center transition-all hover:bg-white" style="box-shadow: 4px 4px 0px 0px #ffffff;">
                                        <p class="text-sm font-black text-white group-hover:text-black">
                                            👥 KELOLA SEMUA USER
                                        </p>
                                    </div>
                                </Link>
                                <Link :href="route('dashboard')" class="block">
                                    <div class="group border-4 border-white bg-black p-4 text-center transition-all hover:bg-white" style="box-shadow: 4px 4px 0px 0px #ffffff;">
                                        <p class="text-sm font-black text-white group-hover:text-black">
                                            📊 LIHAT PROJECT
                                        </p>
                                    </div>
                                </Link>
                            </div>
                        </AcernityCard>
                    </div>
                </div>

                <!-- Recent Projects -->
                <AcernityCard accent>
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-lg font-black uppercase tracking-wider text-yellow-400">
                            📦 PROJECT TERBARU
                        </h3>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="project in stats.recent_projects"
                            :key="project.id"
                            class="group border-4 border-white bg-black p-4 transition-all hover:bg-white hover:text-black"
                            style="box-shadow: 4px 4px 0px 0px #ffffff;"
                        >
                            <div class="mb-3 flex items-start justify-between">
                                <AcernityBadge
                                    :variant="project.status === 'published' ? 'success' : 'warning'"
                                >
                                    {{ project.status.toUpperCase() }}
                                </AcernityBadge>
                            </div>
                            <p class="mb-2 line-clamp-2 text-sm font-black text-white group-hover:text-black">
                                {{ project.prompt || 'Untitled Project' }}
                            </p>
                            <div class="mt-3 flex items-center gap-2 text-xs font-bold text-gray-400 group-hover:text-gray-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>{{ project.user?.name || 'Unknown' }}</span>
                            </div>
                            <p class="mt-1 text-xs font-bold text-gray-400 group-hover:text-gray-600">
                                {{ new Date(project.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                            </p>
                        </div>
                        <div
                            v-if="!stats.recent_projects || stats.recent_projects.length === 0"
                            class="col-span-full py-12 text-center"
                        >
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center border-4 border-white bg-gray-800">
                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4m14 0V9a2 2 0 00-2-2H7a2 2 0 00-2 2v2" />
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-400">
                                Belum ada project dibuat
                            </p>
                        </div>
                    </div>
                </AcernityCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
