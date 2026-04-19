<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50 dark:bg-gray-950">
        <div class="w-full max-w-sm">
            <!-- Logo / Title -->
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $t('app.name') }}
                </h1>
                <span class="mt-1 inline-block rounded bg-primary-600 px-2 py-0.5 text-xs font-semibold text-white">
                    {{ $t('app.demo_badge') }}
                </span>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('app.demo_notice') }}
                </p>
            </div>

            <!-- Form -->
            <div class="rounded-xl border bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <form @submit.prevent="handleLogin" class="space-y-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('auth.email') }}
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('auth.password') }}
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <input v-model="form.remember" type="checkbox" class="rounded border-gray-300" />
                            {{ $t('auth.remember_me') }}
                        </label>
                    </div>

                    <p v-if="error" class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-600 dark:bg-red-900/20 dark:text-red-400">
                        {{ error }}
                    </p>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-60"
                    >
                        {{ loading ? $t('auth.signing_in') : $t('auth.sign_in') }}
                    </button>
                </form>

                <!-- Demo credentials hint -->
                <div class="mt-6 rounded-md bg-gray-50 p-3 dark:bg-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <strong>Demo:</strong> demo@textmessage.dev / password
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = ref({ email: 'demo@textmessage.dev', password: 'password', remember: false })
const loading = ref(false)
const error = ref('')

async function handleLogin() {
    loading.value = true
    error.value = ''

    try {
        await auth.login(form.value.email, form.value.password, form.value.remember)
        router.push('/')
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } } }
        error.value = err.response?.data?.message ?? 'Login failed. Please try again.'
    } finally {
        loading.value = false
    }
}
</script>
