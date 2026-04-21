<template>
    <div class="flex h-screen bg-gray-50 dark:bg-gray-950">

        <!-- Mobile sidebar backdrop -->
        <Transition name="fade">
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-20 bg-black/50 lg:hidden"
                @click="sidebarOpen = false"
            />
        </Transition>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-30 flex w-52 flex-shrink-0 flex-col bg-gray-900 dark:bg-gray-950 transition-transform duration-200 lg:static lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo + close button (mobile) -->
            <div class="flex h-14 items-center justify-between px-4">
                <div class="flex items-center gap-2">
                    <span class="text-lg font-bold text-white">TMM</span>
                    <span class="rounded bg-primary-600 px-1.5 py-0.5 text-xs font-semibold text-white">
                        {{ $t('app.demo_badge') }}
                    </span>
                </div>
                <button
                    class="rounded p-1 text-gray-400 hover:text-white lg:hidden"
                    @click="sidebarOpen = false"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-0.5 px-2 py-2">
                <RouterLink
                    v-for="item in navigation"
                    :key="item.name"
                    :to="item.to"
                    :class="[
                        'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                        isNavActive(item.to)
                            ? 'bg-gray-800 text-white'
                            : 'text-gray-400 hover:bg-gray-800 hover:text-white',
                    ]"
                    @click="sidebarOpen = false"
                >
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    {{ item.label }}
                </RouterLink>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="flex min-h-0 flex-1 flex-col lg:pl-0">
            <!-- Top bar -->
            <header class="flex h-14 flex-shrink-0 items-center justify-between border-b bg-white px-4 dark:bg-gray-900 sm:px-6">
                <div class="flex items-center gap-3">
                    <!-- Hamburger (mobile only) -->
                    <button
                        class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 lg:hidden"
                        @click="sidebarOpen = true"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Search placeholder (hidden on very small screens) -->
                    <div class="hidden items-center gap-2 rounded-md border bg-gray-50 px-3 py-1.5 text-sm text-gray-400 dark:border-gray-700 dark:bg-gray-800 sm:flex">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>{{ $t('common.search_placeholder') }}</span>
                        <kbd class="rounded border px-1 text-xs dark:border-gray-600">⌘K</kbd>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-4">
                    <!-- Credits (hidden on xs) -->
                    <span class="hidden text-sm text-gray-600 dark:text-gray-400 sm:block">
                        {{ $t('common.credits') }}: <strong>{{ auth.organization?.credits?.toLocaleString() ?? '—' }}</strong>
                    </span>

                    <!-- Account switcher -->
                    <UiDropdown align="right" width="sm">
                        <template #trigger="{ open, toggle }">
                            <button
                                class="flex items-center gap-1.5 rounded-md px-2 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 sm:gap-2 sm:px-3"
                                @click="toggle"
                            >
                                <span class="max-w-[100px] truncate sm:max-w-[160px]">{{ auth.organization?.name }}</span>
                                <svg
                                    class="h-4 w-4 flex-shrink-0 transition-transform"
                                    :class="open ? 'rotate-180' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </template>

                        <template #default="{ close }">
                            <div class="py-1">
                                <p class="px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                    {{ $t('settings.accounts') }}
                                </p>
                                <button
                                    v-for="org in auth.organizations"
                                    :key="org.id"
                                    :class="[
                                        'flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition-colors',
                                        org.id === auth.organization?.id
                                            ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300'
                                            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700',
                                    ]"
                                    @click="switchOrg(org.id, close)"
                                >
                                    <span class="flex-1 truncate">{{ org.name }}</span>
                                    <svg v-if="org.id === auth.organization?.id" class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </UiDropdown>

                    <!-- Theme toggle -->
                    <button
                        class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                        @click="toggleTheme"
                    >
                        <svg v-if="isDark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    <!-- Language selector (hidden on xs) -->
                    <select
                        v-model="currentLocale"
                        class="hidden rounded-md border bg-transparent px-2 py-1 text-sm dark:border-gray-700 dark:text-gray-300 sm:block"
                        @change="changeLocale"
                    >
                        <option value="en">EN</option>
                        <option value="pt-BR">PT</option>
                        <option value="es">ES</option>
                    </select>

                    <!-- Avatar dropdown -->
                    <UiDropdown align="right" width="sm">
                        <template #trigger="{ toggle }">
                            <button
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                @click="toggle"
                            >
                                {{ userInitials }}
                            </button>
                        </template>

                        <template #default="{ close }">
                            <div class="py-1">
                                <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth.user?.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth.user?.email }}</p>
                                </div>
                                <!-- Language selector (mobile, inside dropdown) -->
                                <div class="flex items-center gap-2 px-3 py-2 sm:hidden">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('settings.language') }}:</span>
                                    <select
                                        v-model="currentLocale"
                                        class="flex-1 rounded border bg-transparent px-1 py-0.5 text-xs dark:border-gray-600 dark:text-gray-300"
                                        @change="changeLocale"
                                    >
                                        <option value="en">EN</option>
                                        <option value="pt-BR">PT</option>
                                        <option value="es">ES</option>
                                    </select>
                                </div>
                                <RouterLink
                                    to="/settings"
                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                                    @click="close()"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ $t('settings.account') }}
                                </RouterLink>
                                <div class="my-1 border-t border-gray-100 dark:border-gray-700" />
                                <button
                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                    @click="handleLogout(close)"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    {{ $t('auth.sign_out') }}
                                </button>
                            </div>
                        </template>
                    </UiDropdown>
                </div>
            </header>

            <!-- Demo notice banner -->
            <div class="flex items-center justify-center gap-2 border-b border-amber-300/40 bg-amber-50 px-4 py-2 dark:border-amber-700/40 dark:bg-amber-950/40">
                <svg class="h-3.5 w-3.5 flex-shrink-0 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-amber-700 dark:text-amber-300">
                    <span class="font-semibold">{{ $t('app.demo_badge') }}:</span>
                    {{ $t('app.demo_notice') }}
                </p>
            </div>

            <!-- Page content -->
            <main class="flex-1 overflow-auto p-4 sm:p-6">
                <RouterView :key="auth.organization?.id" />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import UiDropdown from '@/components/ui/UiDropdown.vue'

const { t, locale } = useI18n()
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const currentLocale = ref(locale.value)
const isDark = ref(document.documentElement.classList.contains('dark'))
const sidebarOpen = ref(false)

const userInitials = computed(() => {
    if (!auth.user) return '?'
    return auth.user.name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase()
})

const ICONS = {
    dashboard:    'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    inbox:        'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
    subscribers:  'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    messages:     'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
    keywords:     'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z',
    polls:        'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    integrations: 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
}

const navigation = computed(() => [
    { name: 'dashboard',    to: '/',             label: t('nav.dashboard'),    icon: ICONS.dashboard },
    { name: 'inbox',        to: '/inbox',         label: t('nav.inbox'),        icon: ICONS.inbox },
    { name: 'subscribers',  to: '/subscribers',   label: t('nav.subscribers'),  icon: ICONS.subscribers },
    { name: 'messages',     to: '/messages',      label: t('nav.messages'),     icon: ICONS.messages },
    { name: 'keywords',     to: '/keywords',      label: t('nav.keywords'),     icon: ICONS.keywords },
    { name: 'polls',        to: '/polls',         label: t('nav.polls'),        icon: ICONS.polls },
    { name: 'integrations', to: '/integrations',  label: t('nav.integrations'), icon: ICONS.integrations },
])

function isNavActive(to: string): boolean {
    if (to === '/') return route.path === '/'
    return route.path.startsWith(to)
}

function toggleTheme() {
    const theme = isDark.value ? 'light' : 'dark'
    isDark.value = !isDark.value
    auth.setTheme(theme)
}

function changeLocale() {
    locale.value = currentLocale.value
    localStorage.setItem('locale', currentLocale.value)
}

async function switchOrg(id: number, close: () => void) {
    close()
    await auth.switchOrganization(id)
}

async function handleLogout(close: () => void) {
    close()
    await auth.logout()
    router.push('/login')
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
