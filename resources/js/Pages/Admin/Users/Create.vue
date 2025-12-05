<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityInput from '@/Components/Acernity/Input.vue';
import AcernitySelect from '@/Components/Acernity/Select.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
});

const submit = () => {
    form.post(route('admin.users.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Tambah User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black text-white">
                        TAMBAH USER BARU
                    </h2>
                    <p class="mt-1 text-sm font-bold text-gray-400">
                        BUAT AKUN PENGGUNA BARU
                    </p>
                </div>
                <Link :href="route('admin.users.index')">
                    <AcernityButton variant="ghost">
                        ← KEMBALI
                    </AcernityButton>
                </Link>
            </div>
        </template>

        <div class="py-8 bg-black min-h-screen">
            <div class="mx-auto max-w-2xl px-6">
                <AcernityCard accent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <AcernityInput
                            id="name"
                            v-model="form.name"
                            label="Nama"
                            placeholder="Nama lengkap"
                            :error="form.errors.name"
                            required
                        />

                        <AcernityInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            label="Email"
                            placeholder="email@example.com"
                            :error="form.errors.email"
                            required
                        />

                        <AcernitySelect
                            id="role"
                            v-model="form.role"
                            label="Role"
                            :error="form.errors.role"
                            required
                        >
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </AcernitySelect>

                        <AcernityInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            label="Password"
                            placeholder="Minimal 8 karakter"
                            :error="form.errors.password"
                            required
                        />

                        <AcernityInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            label="Konfirmasi Password"
                            placeholder="Ulangi password"
                            :error="form.errors.password_confirmation"
                            required
                        />

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('admin.users.index')">
                                <AcernityButton variant="ghost">
                                    BATAL
                                </AcernityButton>
                            </Link>
                            <AcernityButton
                                type="submit"
                                variant="accent"
                                :processing="form.processing"
                            >
                                SIMPAN USER
                            </AcernityButton>
                        </div>
                    </form>
                </AcernityCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

