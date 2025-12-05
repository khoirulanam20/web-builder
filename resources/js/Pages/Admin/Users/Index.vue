<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { showConfirm } from '@/plugins/swal';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityBadge from '@/Components/Acernity/Badge.vue';
import AcernityInput from '@/Components/Acernity/Input.vue';
import AcernitySelect from '@/Components/Acernity/Select.vue';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchForm = useForm({
    search: props.filters.search || '',
    role: props.filters.role || '',
});

const handleSearch = () => {
    searchForm.get(route('admin.users.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleDelete = (userId, userName) => {
    showConfirm({
        title: 'Hapus User?',
        text: `Apakah Anda yakin ingin menghapus user "${userName}"? Tindakan ini tidak dapat dibatalkan.`,
        icon: 'warning',
        confirmText: 'Ya, Hapus',
        confirmColor: '#ef4444',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.users.destroy', userId), {
                onSuccess: () => {
                    // Toast akan muncul otomatis
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Kelola Pengguna" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black text-white">
                        KELOLA PENGGUNA
                    </h2>
                    <p class="mt-1 text-sm font-bold text-gray-400">
                        KELOLA SEMUA PENGGUNA APLIKASI
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="route('admin.dashboard')">
                        <AcernityButton variant="ghost">
                            ← KEMBALI
                        </AcernityButton>
                    </Link>
                    <Link :href="route('admin.users.create')">
                        <AcernityButton variant="accent">
                            + TAMBAH USER
                        </AcernityButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 bg-black min-h-screen">
            <div class="mx-auto max-w-7xl px-6">
                <!-- Filters -->
                <AcernityCard accent class="mb-6">
                    <form @submit.prevent="handleSearch" class="grid gap-4 sm:grid-cols-3">
                        <AcernityInput
                            v-model="searchForm.search"
                            label="Cari User"
                            placeholder="Nama atau email..."
                        />
                        <AcernitySelect
                            v-model="searchForm.role"
                            label="Filter Role"
                        >
                            <option value="">Semua Role</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </AcernitySelect>
                        <div class="flex items-end">
                            <AcernityButton
                                type="submit"
                                variant="primary"
                                :processing="searchForm.processing"
                                class="w-full"
                            >
                                CARI
                            </AcernityButton>
                        </div>
                    </form>
                </AcernityCard>

                <!-- Users Table -->
                <AcernityCard accent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-4 border-white">
                                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-white">
                                        NAMA
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-white">
                                        EMAIL
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-white">
                                        ROLE
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-black uppercase text-white">
                                        DIBUAT
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-black uppercase text-white">
                                        AKSI
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="hover:bg-white/5"
                                >
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-black text-white">
                                            {{ user.name }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-bold text-gray-400">
                                            {{ user.email }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <AcernityBadge
                                            :variant="user.role === 'admin' ? 'accent' : 'primary'"
                                        >
                                            {{ user.role }}
                                        </AcernityBadge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-xs font-bold text-gray-400">
                                            {{ new Date(user.created_at).toLocaleDateString('id-ID') }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link :href="route('admin.users.show', user.id)">
                                                <AcernityButton variant="primary" class="text-xs">
                                                    LIHAT
                                                </AcernityButton>
                                            </Link>
                                            <Link :href="route('admin.users.edit', user.id)">
                                                <AcernityButton variant="accent" class="text-xs">
                                                    EDIT
                                                </AcernityButton>
                                            </Link>
                                            <AcernityButton
                                                variant="ghost"
                                                @click="handleDelete(user.id, user.name)"
                                                class="text-xs text-red-400 hover:text-red-500"
                                            >
                                                HAPUS
                                            </AcernityButton>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-sm font-bold text-gray-400">
                                        Tidak ada user ditemukan
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="users.links && users.links.length > 3"
                        class="mt-6 flex items-center justify-between border-t-4 border-white pt-4"
                    >
                        <div class="text-sm font-bold text-gray-400">
                            Menampilkan {{ users.from }} - {{ users.to }} dari {{ users.total }} user
                        </div>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in users.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    link.active
                                        ? 'border-4 border-yellow-400 bg-yellow-400 text-black'
                                        : 'border-4 border-white bg-black text-white hover:bg-white hover:text-black',
                                    'px-3 py-2 text-xs font-black transition-all',
                                ]"
                                style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </AcernityCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

