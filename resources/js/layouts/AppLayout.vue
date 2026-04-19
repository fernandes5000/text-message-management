<template>
    <div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-950">
        <!-- Sidebar -->
        <aside class="flex w-48 flex-shrink-0 flex-col bg-gray-900 dark:bg-gray-950">
            <!-- Logo -->
            <div class="flex h-14 items-center px-4">
                <span class="text-lg font-bold text-white">TMM</span>
                <span class="ml-2 rounded bg-primary-600 px-1.5 py-0.5 text-xs font-semibold text-white">
                    {{ $t('app.demo_badge') }}
                </span>
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
                >
                    <component :is="item.icon" class="h-5 w-5 flex-shrink-0" />
                    {{ item.label }}
                </RouterLink>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="flex h-14 items-center justify-between border-b bg-white px-6 dark:bg-gray-900">
                <!-- Search placeholder -->
                <div class="flex items-center gap-2 rounded-md border bg-gray-50 px-3 py-1.5 text-sm text-gray-400 dark:bg-gray-800 dark:border-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>{{ $t('common.search') }} everything...</span>
                    <kbd class="rounded border px-1 text-xs dark:border-gray-600">⌘K</kbd>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Credits -->
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Credits: <strong>{{ auth.organization?.credits?.toLocaleString() ?? '—' }}</strong>
                    </span>

                    <!-- Account switcher -->
                    <button
                        class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                        @click="showAccountMenu = !showAccountMenu"
                    >
                        {{ auth.organization?.name }}
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

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

                    <!-- Language selector -->
                    <select
                        v-model="currentLocale"
                        class="rounded-md border bg-transparent px-2 py-1 text-sm dark:border-gray-700 dark:text-gray-300"
                        @change="changeLocale"
                    >
                        <option value="en">EN</option>
                        <option value="pt-BR">PT</option>
                        <option value="es">ES</option>
                    </select>

                    <!-- Avatar -->
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-medium text-white">
                        {{ userInitials }}
                    </div>
                </div>
            </header>

            <!-- Demo notice banner -->
            <div class="flex items-center justify-center gap-2 bg-primary-600 px-4 py-1 text-xs text-white">
                <span class="font-semibold">{{ $t('app.demo_badge') }}</span>
                <span>{{ $t('app.demo_notice') }}</span>
            </div>

            <!-- Page content -->
            <main class="flex-1 overflow-auto p-6">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t, locale } = useI18n()
const auth = useAuthStore()
const route = useRoute()

const showAccountMenu = ref(false)
const currentLocale = ref(locale.value)

// Reactive ref so the icon updates immediately on toggle without needing a DOM read
const isDark = ref(document.documentElement.classList.contains('dark'))

const userInitials = computed(() => {
    if (!auth.user) return '?'
    return auth.user.name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase()
})

const navigation = computed(() => [
    { name: 'dashboard', to: '/', label: t('nav.dashboard'), icon: 'svg' },
    { name: 'inbox', to: '/inbox', label: t('nav.inbox'), icon: 'svg' },
    { name: 'subscribers', to: '/subscribers', label: t('nav.subscribers'), icon: 'svg' },
    { name: 'messages', to: '/messages', label: t('nav.messages'), icon: 'svg' },
    { name: 'keywords', to: '/keywords', label: t('nav.keywords'), icon: 'svg' },
    { name: 'polls', to: '/polls', label: t('nav.polls'), icon: 'svg' },
    { name: 'integrations', to: '/integrations', label: t('nav.integrations'), icon: 'svg' },
])

// Root path uses exact match; all others match by prefix to cover nested routes
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
</script>
