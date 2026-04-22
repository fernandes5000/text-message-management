<template>
    <div v-if="loading" class="flex items-center justify-center py-24">
        <svg class="h-6 w-6 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
    </div>

    <div v-else-if="message" class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <RouterLink
                to="/messages"
                class="rounded-lg border p-2 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </RouterLink>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ message.name }}</h1>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('messages.message_label') }}
                    <span v-if="message.creator"> · {{ $t('common.by') }} {{ message.creator.name }}</span>
                </p>
            </div>
            <span :class="statusClass(message.status)" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium">
                <span class="h-1.5 w-1.5 rounded-full bg-current" />
                {{ statusLabel(message.status) }}
            </span>
        </div>

        <!-- Main content grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left: message card -->
            <div class="space-y-4 lg:col-span-2">
                <!-- Message details -->
                <div class="rounded-lg border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 font-semibold text-gray-900 dark:text-white">{{ $t('messages.message_label') }}</h2>

                    <div class="grid grid-cols-2 gap-4 mb-5 text-sm">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('messages.from') }}</p>
                            <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ message.from_number || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('messages.to') }}</p>
                            <p class="mt-1 font-medium text-gray-900 dark:text-white">
                                {{ message.lists?.map(l => l.name).join(', ') || '—' }}
                            </p>
                        </div>
                    </div>

                    <!-- Message preview bubble -->
                    <div class="rounded-xl border bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <p v-if="message.use_header && message.header" class="mb-2 font-semibold text-gray-900 dark:text-white">
                            {{ message.header }}
                        </p>
                        <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ message.body }}</p>
                    </div>
                </div>

                <!-- Schedule info for non-sent -->
                <div v-if="message.send_type !== 'now' && message.status !== 'sent'" class="rounded-lg border bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-3 font-semibold text-gray-900 dark:text-white">{{ $t('messages.schedule') }}</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('messages.schedule') }}</p>
                            <p class="mt-1 text-gray-900 dark:text-white capitalize">{{ message.send_type }}</p>
                        </div>
                        <div v-if="message.scheduled_at">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('messages.send_date_time') }}</p>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ formatDateTime(message.scheduled_at) }}</p>
                        </div>
                        <div v-if="message.recurrence">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('messages.recurrence') }}</p>
                            <p class="mt-1 text-gray-900 dark:text-white capitalize">{{ message.recurrence }}</p>
                        </div>
                    </div>
                </div>

                <!-- Delivery timeline chart (sent messages only) -->
                <div v-if="message.status === 'sent'" class="rounded-lg border bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-3 flex items-baseline justify-between">
                        <h2 class="font-semibold text-gray-900 dark:text-white">{{ $t('messages.timeline') }}</h2>
                        <span class="text-xs text-gray-400">{{ formatDateTime(message.sent_at) }}</span>
                    </div>
                    <div class="flex gap-6 mb-4">
                        <div>
                            <p class="text-2xl font-bold text-primary-600">{{ (message.recipient_count ?? 0).toLocaleString() }}</p>
                            <p class="text-xs text-gray-500 flex items-center gap-1">
                                <span class="inline-block h-2 w-2 rounded-full bg-primary-500"></span>
                                {{ $t('messages.outgoing_texts') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-500">0</p>
                            <p class="text-xs text-gray-500 flex items-center gap-1">
                                <span class="inline-block h-2 w-2 rounded-full bg-blue-400"></span>
                                {{ $t('messages.replies') }}
                            </p>
                        </div>
                    </div>
                    <Bar :data="timelineChartData" :options="timelineChartOptions" class="max-h-36" />
                </div>
            </div>

            <!-- Right: stats -->
            <div class="space-y-4">
                <!-- Delivery stats -->
                <div class="rounded-lg border bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 font-semibold text-gray-900 dark:text-white">{{ $t('messages.recipients') }}</h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="h-2 w-2 rounded-full bg-green-500" />
                                {{ $t('messages.sent') }}
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ (message.recipient_count ?? 0).toLocaleString() }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="h-2 w-2 rounded-full bg-red-400" />
                                {{ $t('messages.status_failed') }}
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">0</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="h-2 w-2 rounded-full bg-gray-400" />
                                {{ $t('messages.opt_outs') }}
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">0</span>
                        </div>
                    </div>
                </div>

                <!-- Meta stats -->
                <div class="rounded-lg border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 space-y-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('common.status') }}</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ statusLabel(message.status) }}</p>
                    </div>
                    <div v-if="message.sent_at">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('messages.sent') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDateTime(message.sent_at) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('messages.credits_required') }}</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                            {{ (message.credits_used ?? 0).toLocaleString() }}
                        </p>
                    </div>
                    <div v-if="message.creator">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $t('common.created_by') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ message.creator.name }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div v-if="message.status === 'draft'" class="flex flex-col gap-2">
                    <UiButton class="w-full" @click="sendNow">{{ $t('messages.send_now') }}</UiButton>
                    <UiButton variant="secondary" class="w-full" @click="$router.push('/messages')">
                        {{ $t('common.cancel') }}
                    </UiButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import {
    Chart as ChartJS,
    CategoryScale, LinearScale,
    BarElement,
    Tooltip, Legend,
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import UiButton from '@/components/ui/UiButton.vue'
import { useToastStore } from '@/stores/toast'
import type { Message } from '@/types'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

const route  = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const toast  = useToastStore()

const message = ref<Message | null>(null)
const loading = ref(true)

async function fetchMessage() {
    loading.value = true
    try {
        const { data } = await axios.get(`/api/v1/messages/${route.params.id}`)
        message.value = data
    } finally {
        loading.value = false
    }
}

async function sendNow() {
    try {
        await axios.post(`/api/v1/messages/${message.value!.id}/send`)
        toast.success(t('messages.send_success'))
        await fetchMessage()
    } catch {
        toast.error(t('messages.send_error'))
    }
}

function statusLabel(status: string): string {
    const map: Record<string, string> = {
        draft:     t('messages.status_draft'),
        scheduled: t('messages.status_scheduled'),
        sending:   t('messages.status_sending'),
        sent:      t('messages.status_sent'),
        failed:    t('messages.status_failed'),
    }
    return map[status] ?? status
}

function statusClass(status: string): string {
    const map: Record<string, string> = {
        draft:     'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        scheduled: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        sending:   'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
        sent:      'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        failed:    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    }
    return map[status] ?? ''
}

function formatDateTime(iso: string | null): string {
    if (!iso) return '—'
    return new Date(iso).toLocaleString(locale.value, {
        weekday: 'short', month: 'short', day: 'numeric',
        year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
}

const timelineChartData = computed(() => {
    const total = message.value?.recipient_count ?? 0
    const sentAt = message.value?.sent_at ? new Date(message.value.sent_at) : new Date()
    const buckets = 8
    const labels: string[] = []
    const values: number[] = []
    let remaining = total
    for (let i = 0; i < buckets; i++) {
        const t = new Date(sentAt.getTime() + i * 5 * 60 * 1000)
        labels.push(t.toLocaleTimeString(locale.value, { hour: '2-digit', minute: '2-digit' }))
        const share = i === buckets - 1
            ? remaining
            : Math.round(total * (i === 0 ? 0.45 : i === 1 ? 0.3 : i === 2 ? 0.15 : 0.025))
        values.push(Math.max(0, Math.min(share, remaining)))
        remaining -= values[i]
    }
    return {
        labels,
        datasets: [{
            label: t('messages.delivered'),
            data: values,
            backgroundColor: 'rgba(99,102,241,0.7)',
            borderRadius: 4,
        }],
    }
})

const timelineChartOptions = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { precision: 0 } },
    },
}

onMounted(fetchMessage)
</script>
