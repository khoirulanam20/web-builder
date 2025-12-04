<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AcernityButton from '@/Components/Acernity/Button.vue';
import AcernityCard from '@/Components/Acernity/Card.vue';
import AcernityBadge from '@/Components/Acernity/Badge.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="AI Web Generator" />

    <div class="min-h-screen bg-black text-white">
        <!-- Grid Pattern Background -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="grid-pattern absolute inset-0"></div>
        </div>

        <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8">
            <!-- Header -->
            <header
                class="mb-8 flex items-center justify-between border-4 border-white bg-black p-4"
                style="box-shadow: 4px 4px 0px 0px #ffff00;"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center border-4 border-white bg-yellow-400"
                        style="box-shadow: 2px 2px 0px 0px #ffffff;"
                    >
                        <span class="text-lg font-black text-black">
                            AI
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-black uppercase tracking-tight text-white">
                            WEB GENERATOR
                        </p>
                        <p class="text-xs font-bold text-gray-400">
                            PROMPT → LANDING PAGE
                        </p>
                    </div>
                </div>

                <nav
                    v-if="canLogin"
                    class="flex items-center gap-3"
                >
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="border-4 border-white bg-black px-4 py-2 text-sm font-black uppercase text-white transition-all hover:-translate-x-0.5 hover:-translate-y-0.5"
                        style="box-shadow: 4px 4px 0px 0px #ffffff;"
                    >
                        DASHBOARD
                    </Link>
                    <template v-else>
                        <Link :href="route('login')">
                            <AcernityButton variant="ghost">
                                MASUK
                            </AcernityButton>
                        </Link>
                        <Link :href="route('register')">
                            <AcernityButton variant="accent">
                                DAFTAR
                            </AcernityButton>
                        </Link>
                    </template>
                </nav>
            </header>

            <!-- Main Content -->
            <main class="grid flex-1 gap-8 lg:grid-cols-[1.2fr_1fr] lg:items-center">
                <!-- Left Section -->
                <section class="space-y-8">
                    <div class="space-y-6">
                        <AcernityBadge variant="warning">
                            POWERED BY XAI GROK 4.1 FAST & GOOGLE GEMINI
                        </AcernityBadge>

                        <div class="space-y-4">
                            <h1 class="text-5xl font-black uppercase leading-tight text-white sm:text-6xl lg:text-7xl">
                                BUAT WEBSITE PROFESIONAL
                                <span
                                    class="block text-yellow-400"
                                    style="text-shadow: 4px 4px 0px #ffffff;"
                                >
                                    HANYA DENGAN SATU PROMPT
                                </span>
                            </h1>
                            <p class="max-w-2xl text-lg font-bold leading-relaxed text-gray-300">
                                Tulis deskripsi singkat tentang bisnis atau produk Anda,
                                sistem akan menghasilkan landing page HTML + TailwindCSS + JS
                                yang responsif, siap di-preview, di-share via subdomain,
                                dan di-download sebagai source code.
                            </p>
                        </div>
                    </div>

                    <AcernityCard accent>
                        <p class="mb-4 text-xs font-black uppercase tracking-wider text-yellow-400">
                            CONTOH PROMPT
                        </p>
                        <div class="space-y-3 text-sm font-bold text-white">
                            <p>
                                "Buat landing page
                                <span class="text-yellow-400">
                                    jasa interior design
                                </span>
                                warna krem dengan hero besar, section layanan, portfolio,
                                dan CTA tombol WhatsApp."
                            </p>
                            <p>
                                "Company profile
                                <span class="text-cyan-400">
                                    bengkel motor
                                </span>
                                warna hitam kuning dengan highlight layanan, galeri, dan
                                form kontak."
                            </p>
                        </div>

                        <div
                            v-if="canLogin"
                            class="mt-6 flex flex-wrap items-center gap-4"
                        >
                            <Link :href="route($page.props.auth?.user ? 'dashboard' : 'login')">
                                <AcernityButton variant="accent">
                                    {{ $page.props.auth?.user ? 'MASUK KE DASHBOARD' : 'MULAI DARI PROMPT' }}
                                </AcernityButton>
                            </Link>
                            <p class="text-xs font-bold text-gray-400">
                                Tidak perlu install apa pun. Cukup login, tulis prompt, preview, publish.
                            </p>
                        </div>
                    </AcernityCard>

                    <!-- Features Grid -->
                    <div class="grid gap-6 text-sm sm:grid-cols-3">
                        <div
                            class="border-4 border-white bg-black p-4"
                            style="box-shadow: 4px 4px 0px 0px #ffffff;"
                        >
                            <p class="mb-2 font-black uppercase text-white">
                                PROMPT → UI
                            </p>
                            <p class="text-xs font-bold text-gray-400">
                                Dari ide ke landing page HTML + Tailwind responsif dalam
                                <span class="text-yellow-400">&lt; 10 DETIK</span>.
                            </p>
                        </div>
                        <div
                            class="border-4 border-white bg-black p-4"
                            style="box-shadow: 4px 4px 0px 0px #ffffff;"
                        >
                            <p class="mb-2 font-black uppercase text-white">
                                PREVIEW & SUBDOMAIN
                            </p>
                            <p class="text-xs font-bold text-gray-400">
                                Live preview aman via iframe sandbox, publish ke subdomain
                                unik untuk dikirim ke klien.
                            </p>
                        </div>
                        <div
                            class="border-4 border-white bg-black p-4"
                            style="box-shadow: 4px 4px 0px 0px #ffffff;"
                        >
                            <p class="mb-2 font-black uppercase text-white">
                                DOWNLOAD SOURCE
                            </p>
                            <p class="text-xs font-bold text-gray-400">
                                Download paket
                                <span class="text-cyan-400 font-black">HTML / CSS / JS</span> sebagai ZIP untuk
                                pengembangan lanjutan.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Right Section - Demo Flow -->
                <section class="hidden lg:block">
                    <AcernityCard accent>
                        <div class="mb-6 flex items-center justify-between">
                            <AcernityBadge variant="info">
                                DEMO ALUR
                            </AcernityBadge>
                            <span class="text-xs font-bold text-gray-400">
                                Laravel {{ laravelVersion }} • PHP {{ phpVersion }}
                            </span>
                        </div>

                        <ol class="space-y-6">
                            <li class="flex gap-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center border-4 border-white bg-yellow-400 font-black text-black"
                                    style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                >
                                    1
                                </div>
                                <div class="space-y-1">
                                    <p class="font-black uppercase text-white">
                                        LOGIN / DAFTAR AKUN
                                    </p>
                                    <p class="text-sm font-bold text-gray-400">
                                        Masuk sebagai agency, freelancer, atau owner bisnis.
                                        Semua project tersimpan di akun pribadi Anda.
                                    </p>
                                </div>
                            </li>
                            <li class="flex gap-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center border-4 border-white bg-yellow-400 font-black text-black"
                                    style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                >
                                    2
                                </div>
                                <div class="space-y-1">
                                    <p class="font-black uppercase text-white">
                                        TULIS PROMPT → GENERATE
                                    </p>
                                    <p class="text-sm font-bold text-gray-400">
                                        Jelaskan warna, gaya, dan section yang diinginkan.
                                        AI akan menghasilkan UI siap pakai dengan TailwindCSS.
                                    </p>
                                </div>
                            </li>
                            <li class="flex gap-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center border-4 border-white bg-yellow-400 font-black text-black"
                                    style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                >
                                    3
                                </div>
                                <div class="space-y-1">
                                    <p class="font-black uppercase text-white">
                                        PREVIEW, PUBLISH, & KIRIM KE KLIEN
                                    </p>
                                    <p class="text-sm font-bold text-gray-400">
                                        Lihat hasil di iframe sandbox, publish ke subdomain,
                                        lalu kirim link live preview ke klien untuk approval cepat.
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </AcernityCard>
                </section>
            </main>
        </div>
    </div>
</template>
