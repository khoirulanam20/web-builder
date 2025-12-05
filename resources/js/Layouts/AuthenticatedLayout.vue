<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import { useFlashMessages } from '@/composables/useFlashMessages';

const showingNavigationDropdown = ref(false);

// Handle flash messages globally in layout
useFlashMessages();
</script>

<template>
    <div class="min-h-screen bg-black">
        <!-- Grid Pattern Background -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="grid-pattern absolute inset-0"></div>
        </div>

        <div class="relative">
            <!-- Navigation -->
            <nav class="border-b-4 border-white bg-black">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center border-4 border-white bg-yellow-400"
                                            style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                        >
                                            <span class="text-lg font-black text-black">
                                                AI
                                            </span>
                                        </div>
                                        <span class="text-lg font-black text-white">WEB GENERATOR</span>
                                    </div>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <Link
                                    :href="route('dashboard')"
                                    :class="[
                                        route().current('dashboard')
                                            ? 'border-b-4 border-yellow-400 text-white'
                                            : 'border-b-4 border-transparent text-gray-400 hover:border-white hover:text-white',
                                        'inline-flex items-center px-1 pt-1 text-sm font-black transition-colors',
                                    ]"
                                >
                                    DASHBOARD
                                </Link>
                                <Link
                                    v-if="$page.props.auth.user?.isAdmin"
                                    :href="route('admin.dashboard')"
                                    :class="[
                                        route().current('admin.*')
                                            ? 'border-b-4 border-yellow-400 text-white'
                                            : 'border-b-4 border-transparent text-gray-400 hover:border-white hover:text-white',
                                        'inline-flex items-center px-1 pt-1 text-sm font-black transition-colors',
                                    ]"
                                >
                                    ADMIN
                                </Link>
                            </div>
                        </div>

                        <div class="hidden sm:ml-6 sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ml-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex">
                                            <button
                                                type="button"
                                                class="inline-flex items-center border-4 border-white bg-black px-4 py-2 text-sm font-black text-white transition-all hover:bg-white hover:text-black"
                                                style="box-shadow: 2px 2px 0px 0px #ffffff;"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center border-4 border-white bg-black p-2 text-white transition-all hover:bg-white hover:text-black"
                                style="box-shadow: 2px 2px 0px 0px #ffffff;"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden border-t-4 border-white bg-black"
                >
                    <div class="space-y-1 px-2 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            DASHBOARD
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="$page.props.auth.user?.isAdmin"
                            :href="route('admin.dashboard')"
                            :active="route().current('admin.*')"
                        >
                            ADMIN
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t-4 border-white px-4 pb-4 pt-4">
                        <div class="px-2">
                            <div class="text-base font-black text-white">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-bold text-gray-400">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                PROFILE
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                LOG OUT
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                v-if="$slots.header"
                class="border-b-4 border-white bg-black"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
