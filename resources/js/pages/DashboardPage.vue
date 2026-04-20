<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('dashboard.title') }}</h1>
            <div class="flex rounded-lg border bg-white dark:border-gray-700 dark:bg-gray-800">
                <button
                    v-for="p in periods"
                    :key="p.value"
                    :class="[
                        'px-3 py-1.5 text-sm font-medium transition-colors first:rounded-l-lg last:rounded-r-lg',
                        period === p.value
                            ? 'bg-primary-600 text-white'
                            : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700',
                    ]"
                    @click="setPeriod(p.value)"
                >
                    {{ $t('dashboard.periods.' + p.value) }}
                </button>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <div
                v-for="stat in statCards"
                :key="stat.key"
                class="rounded-lg border bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex items-start justify-between">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
                    <span :class="stat.iconBg" class="flex h-7 w-7 items-center justify-center rounded-lg">
                        <svg class="h-4 w-4" :class="stat.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
                        </svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    <span v-if="loading" class="inline-block h-7 w-16 animate-pulse rounded bg-gray-200 dark:bg-gray-700" />
                    <template v-else>{{ stat.value.toLocaleString() }}</template>
                </p>
            </div>
        </div>

        <!-- Main chart -->
        <div class="rounded-lg border bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-4 font-semibold text-gray-900 dark:text-white">{{ $t('dashboard.overall_performance') }}</h2>
            <div v-if="loading" class="flex h-48 items-center justify-center">
                <svg class="h-6 w-6 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>
            <Line v-else :data="lineChartData" :options="lineChartOptions" class="max-h-56" />
        </div>

        <!-- Bottom row -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Subscriber sources -->
            <div class="rounded-lg border bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 font-semibold text-gray-900 dark:text-white">{{ $t('dashboard.subscriber_sources') }}</h2>
                <div v-if="loading" class="flex h-36 items-center justify-center">
                    <svg class="h-6 w-6 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="(src, i) in sourceRows"
                        :key="src.label"
                        class="flex items-center gap-3"
                    >
                        <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" :style="{ background: sourceColors[i % sourceColors.length] }" />
                        <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ src.label }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ src.count.toLocaleString() }}</span>
                        <span class="w-10 text-right text-xs text-gray-400">{{ src.pct }}%</span>
                    </div>
                    <div v-if="sourceRows.length === 0" class="text-sm text-gray-400">{{ $t('common.no_results') }}</div>
                </div>
            </div>

            <!-- Recently sent -->
            <div class="rounded-lg border bg-white p-5 dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
                <h2 class="mb-4 font-semibold text-gray-900 dark:text-white">{{ $t('dashboard.recently_sent') }}</h2>
                <div v-if="loading" class="space-y-3">
                    <div v-for="n in 4" :key="n" class="h-8 animate-pulse rounded bg-gray-100 dark:bg-gray-700" />
                </div>
                <table v-else class="min-w-full">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <th class="pb-2">{{ $t('common.name') }}</th>
                            <th class="pb-2">{{ $t('messages.recipients') }}</th>
                            <th class="pb-2 text-right">{{ $t('common.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-if="recentlySent.length === 0">
                            <td colspan="3" class="py-4 text-center text-sm text-gray-400">{{ $t('common.no_results') }}</td>
                        </tr>
                        <tr v-for="msg in recentlySent" :key="msg.id" class="text-sm">
                            <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">
                                <RouterLink :to="`/messages/${msg.id}`" class="hover:text-primary-600 dark:hover:text-primary-400">{{ msg.name }}</RouterLink>
                            </td>
                            <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ msg.recipient_count.toLocaleString() }}</td>
                            <td class="py-2 text-right text-gray-400">{{ formatDate(msg.sent_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js'
import { Line } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend, Filler)

const { t, locale } = useI18n()

const loading  = ref(true)
const period   = ref('1m')

interface Stats {
    total_subscribers: number
    opt_ins: number
    opt_outs: number
    outgoing_texts: number
    incoming_texts: number
}

interface ChartData {
    labels: string[]
    buckets: { opt_ins: number; opt_outs: number; outgoing: number; incoming: number }[]
}

interface RecentMessage {
    id: number
    name: string
    recipient_count: number
    sent_at: string
}

const stats         = ref<Stats>({ total_subscribers: 0, opt_ins: 0, opt_outs: 0, outgoing_texts: 0, incoming_texts: 0 })
const chartData     = ref<ChartData>({ labels: [], buckets: [] })
const sources       = ref<Record<string, number>>({})
const recentlySent  = ref<RecentMessage[]>([])

const periods = [
    { value: '1w' },
    { value: '1m' },
    { value: '6m' },
    { value: '1y' },
]

const statCards = computed(() => [
    {
        key: 'total',
        label: t('dashboard.total_subscribers'),
        value: stats.value.total_subscribers,
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        iconBg: 'bg-primary-50 dark:bg-primary-900/30',
        iconColor: 'text-primary-600 dark:text-primary-400',
    },
    {
        key: 'opt_ins',
        label: t('dashboard.opt_ins'),
        value: stats.value.opt_ins,
        icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
        iconBg: 'bg-green-50 dark:bg-green-900/30',
        iconColor: 'text-green-600 dark:text-green-400',
    },
    {
        key: 'opt_outs',
        label: t('dashboard.opt_outs'),
        value: stats.value.opt_outs,
        icon: 'M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6',
        iconBg: 'bg-red-50 dark:bg-red-900/30',
        iconColor: 'text-red-600 dark:text-red-400',
    },
    {
        key: 'outgoing',
        label: t('dashboard.outgoing_texts'),
        value: stats.value.outgoing_texts,
        icon: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
        iconBg: 'bg-blue-50 dark:bg-blue-900/30',
        iconColor: 'text-blue-600 dark:text-blue-400',
    },
    {
        key: 'incoming',
        label: t('dashboard.incoming_texts'),
        value: stats.value.incoming_texts,
        icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        iconBg: 'bg-amber-50 dark:bg-amber-900/30',
        iconColor: 'text-amber-600 dark:text-amber-400',
    },
])

const lineChartData = computed(() => {
    const labels = chartData.value.labels.map(d =>
        new Date(d).toLocaleDateString(locale.value, { month: 'short', day: 'numeric' })
    )
    const buckets = chartData.value.buckets

    return {
        labels,
        datasets: [
            {
                label: t('dashboard.opt_ins'),
                data: buckets.map(b => b.opt_ins),
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124,58,237,0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
            },
            {
                label: t('dashboard.outgoing_texts'),
                data: buckets.map(b => b.outgoing),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.06)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
            },
            {
                label: t('dashboard.incoming_texts'),
                data: buckets.map(b => b.incoming),
                borderColor: '#d97706',
                backgroundColor: 'rgba(217,119,6,0.06)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
            },
        ],
    }
})

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
            labels: { boxWidth: 10, font: { size: 11 } },
        },
        tooltip: { mode: 'index' as const, intersect: false },
    },
    scales: {
        x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 11 } } },
        y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
    },
}

const sourceColors = ['#7c3aed', '#2563eb', '#16a34a', '#d97706', '#dc2626', '#0891b2']

const sourceKeys: Record<string, string> = {
    import:  'subscribers.source_import',
    keyword: 'subscribers.source_keyword',
    manual:  'subscribers.source_manual',
    api:     'subscribers.source_api',
}

const sourceRows = computed(() => {
    const entries = Object.entries(sources.value).map(([key, count]) => ({
        label: t(sourceKeys[key] ?? key),
        count,
    }))
    const total = entries.reduce((s, e) => s + e.count, 0)
    return entries
        .sort((a, b) => b.count - a.count)
        .map(e => ({ ...e, pct: total ? Math.round((e.count / total) * 100) : 0 }))
})

async function fetchDashboard() {
    loading.value = true
    try {
        const res = await axios.get('/api/v1/dashboard', { params: { period: period.value } })
        stats.value        = res.data.stats
        chartData.value    = res.data.chart_data
        sources.value      = res.data.subscriber_sources
        recentlySent.value = res.data.recently_sent
    } finally {
        loading.value = false
    }
}

function setPeriod(p: string) {
    period.value = p
    fetchDashboard()
}

function formatDate(iso: string | null): string {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString(locale.value, { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(fetchDashboard)
</script>
