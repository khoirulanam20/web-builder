<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityInput from '@/Components/Acernity/Input.vue';
import AcernitySelect from '@/Components/Acernity/Select.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role: props.user.role,
});

const submit = () => {
    form.patch(route('admin.users.update', props.user.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black text-white">
                        EDIT USER
                    </h2>
                    <p class="mt-1 text-sm font-bold text-gray-400">
                        UBAH INFORMASI PENGGUNA
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

                        <div class="border-4 border-white bg-black p-4" style="box-shadow: 2px 2px 0px 0px #ffffff;">
                            <p class="mb-2 text-xs font-black uppercase text-yellow-400">
                                UBAH PASSWORD (OPSIONAL)
                            </p>
                            <p class="text-xs font-bold text-gray-400">
                                Kosongkan jika tidak ingin mengubah password
                            </p>
                        </div>

                        <AcernityInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            label="Password Baru"
                            placeholder="Minimal 8 karakter"
                            :error="form.errors.password"
                        />

                        <AcernityInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            label="Konfirmasi Password Baru"
                            placeholder="Ulangi password baru"
                            :error="form.errors.password_confirmation"
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
                                UPDATE USER
                            </AcernityButton>
                        </div>
                    </form>
                </AcernityCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

