<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('messages.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('messages.subtitle') }}</p>
            </div>
            <UiButton @click="openComposer()">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('messages.create') }}
            </UiButton>
        </div>

        <!-- Stats row -->
        <div class="grid grid-cols-3 gap-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-lg border bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-3">
            <div class="flex rounded-lg border bg-white dark:border-gray-700 dark:bg-gray-800">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    :class="[
                        'px-4 py-2 text-sm font-medium transition-colors first:rounded-l-lg last:rounded-r-lg',
                        activeTab === tab.value
                            ? 'bg-primary-600 text-white'
                            : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700',
                    ]"
                    @click="setTab(tab.value)"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('common.search') + '...'"
                    class="w-full rounded-lg border bg-white py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                    @input="debouncedFetch"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border bg-white dark:border-gray-700 dark:bg-gray-800">
            <div v-if="loading" class="flex items-center justify-center py-16">
                <svg class="h-6 w-6 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>

            <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('common.name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('common.status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('messages.recipients') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('common.date') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr v-if="messages.length === 0">
                        <td colspan="5" class="py-12 text-center text-sm text-gray-400">{{ $t('common.no_results') }}</td>
                    </tr>
                    <tr
                        v-for="msg in messages"
                        :key="msg.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700/40"
                    >
                        <td class="px-4 py-3">
                            <RouterLink
                                :to="{ name: 'messages.show', params: { id: msg.id } }"
                                class="font-medium text-gray-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                            >{{ msg.name }}</RouterLink>
                            <div class="mt-0.5 max-w-xs truncate text-xs text-gray-500 dark:text-gray-400">{{ msg.body }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="statusClass(msg.status)" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium">
                                <span class="h-1.5 w-1.5 rounded-full bg-current" />
                                {{ statusLabel(msg.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            {{ msg.recipient_count > 0 ? msg.recipient_count.toLocaleString() : '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ formatDate(msg.sent_at ?? msg.scheduled_at ?? msg.created_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    v-if="msg.status === 'draft'"
                                    class="rounded px-2 py-1 text-xs font-medium text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/20"
                                    @click="sendNow(msg)"
                                >
                                    {{ $t('messages.send_now') }}
                                </button>
                                <button
                                    v-if="msg.status === 'draft'"
                                    class="rounded px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                    @click="openComposer(msg)"
                                >
                                    {{ $t('common.edit') }}
                                </button>
                                <button
                                    class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                    @click="deleteMessage(msg)"
                                >
                                    {{ $t('common.delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t px-4 py-3 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('common.page_of', { current: meta.current_page, total: meta.last_page }) }}
                </p>
                <div class="flex gap-2">
                    <button
                        :disabled="meta.current_page === 1"
                        class="rounded border px-3 py-1 text-sm disabled:opacity-40 dark:border-gray-600 dark:text-gray-300"
                        @click="changePage(meta.current_page - 1)"
                    >
                        {{ $t('common.prev') }}
                    </button>
                    <button
                        :disabled="meta.current_page === meta.last_page"
                        class="rounded border px-3 py-1 text-sm disabled:opacity-40 dark:border-gray-600 dark:text-gray-300"
                        @click="changePage(meta.current_page + 1)"
                    >
                        {{ $t('common.next') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Composer Slide-over -->
    <Teleport to="body">
        <div
            v-if="composerOpen"
            class="fixed inset-0 z-50 flex items-center justify-end bg-black/50"
            @click.self="composerOpen = false"
        >
            <div class="flex h-full w-full max-w-xl flex-col bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b px-6 py-4 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ editingMessage ? $t('messages.edit') : $t('messages.create') }}
                    </h2>
                    <button class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" @click="composerOpen = false">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('messages.campaign_name') }}</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Weekend Sale"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                        />
                    </div>

                    <!-- From -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('messages.from') }}</label>
                        <input
                            v-model="form.from_number"
                            type="text"
                            placeholder="+1 (555) 000-0000"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                        />
                    </div>

                    <!-- To (lists) -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('messages.to') }}</label>
                        <div class="space-y-1.5 rounded-lg border p-3 dark:border-gray-600">
                            <p v-if="lists.length === 0" class="text-sm text-gray-400">{{ $t('messages.loading_lists') }}</p>
                            <label
                                v-for="list in lists"
                                :key="list.id"
                                class="flex cursor-pointer items-center gap-2 rounded px-1 py-0.5 hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <input
                                    type="checkbox"
                                    :value="list.id"
                                    v-model="form.list_ids"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ list.name }}</span>
                                <span class="text-xs text-gray-400">{{ (list.subscribers_count ?? 0).toLocaleString() }}</span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ $t('messages.est_recipients') }}: <strong>{{ estimatedRecipients.toLocaleString() }}</strong>
                        </p>
                    </div>

                    <!-- Use Header toggle -->
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            :class="[
                                'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                                form.use_header ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-600',
                            ]"
                            @click="form.use_header = !form.use_header"
                        >
                            <span
                                :class="[
                                    'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition',
                                    form.use_header ? 'translate-x-4' : 'translate-x-0',
                                ]"
                            />
                        </button>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('messages.use_header') }}</span>
                    </div>
                    <div v-if="form.use_header">
                        <input
                            v-model="form.header"
                            type="text"
                            :placeholder="$t('messages.header_placeholder')"
                            maxlength="100"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <!-- Body -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('messages.message_label') }}</label>
                        <textarea
                            v-model="form.body"
                            rows="5"
                            maxlength="1600"
                            :placeholder="$t('messages.type_message')"
                            class="w-full resize-none rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                        />
                        <div class="mt-1 flex justify-between text-xs text-gray-400">
                            <span>{{ smsSegments }} {{ $t('common.segments').toLowerCase() }}</span>
                            <span>{{ form.body.length }}/1600</span>
                        </div>
                    </div>

                    <!-- Schedule type -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('messages.schedule') }}</label>
                        <div class="flex gap-2">
                            <button
                                v-for="opt in sendTypeOptions"
                                :key="opt.value"
                                :class="[
                                    'flex-1 rounded-lg border px-3 py-2 text-sm font-medium transition-colors',
                                    form.send_type === opt.value
                                        ? 'border-primary-600 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300'
                                        : 'border-gray-200 text-gray-600 hover:border-gray-300 dark:border-gray-600 dark:text-gray-400',
                                ]"
                                @click="form.send_type = opt.value"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>

                    <div v-if="form.send_type === 'scheduled'">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('messages.send_date_time') }}</label>
                        <input
                            v-model="form.scheduled_at"
                            type="datetime-local"
                            :min="minDateTime"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div v-if="form.send_type === 'recurring'">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('messages.recurrence') }}</label>
                        <select
                            v-model="form.recurrence"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="daily">{{ $t('messages.recurrence_daily') }}</option>
                            <option value="weekly">{{ $t('messages.recurrence_weekly') }}</option>
                            <option value="monthly">{{ $t('messages.recurrence_monthly') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Preview + footer -->
                <div class="border-t dark:border-gray-700">
                    <div class="border-b px-6 py-3 dark:border-gray-700">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $t('messages.preview') }}</p>
                        <div class="flex gap-4">
                            <div class="relative mx-auto w-36 rounded-2xl border-2 border-gray-300 bg-gray-900 p-1 dark:border-gray-600">
                                <div class="rounded-xl bg-gray-800 p-2">
                                    <p class="mb-1 text-center text-[9px] text-gray-400">{{ form.from_number || '+1 (555) 000-0000' }}</p>
                                    <div class="rounded-xl bg-gray-600 p-2">
                                        <p v-if="form.use_header && form.header" class="mb-1 text-[9px] font-bold text-white">{{ form.header }}</p>
                                        <p class="text-[9px] leading-relaxed text-white">{{ form.body || $t('messages.preview_placeholder') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $t('messages.recipients') }}:</span>
                                    <span class="ml-1">{{ estimatedRecipients.toLocaleString() }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $t('messages.credits_required') }}:</span>
                                    <span class="ml-1">{{ estimatedCredits.toLocaleString() }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $t('common.segments') }}:</span>
                                    <span class="ml-1">{{ smsSegments }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 px-6 py-4">
                        <button
                            class="flex-1 rounded-lg border px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                            :disabled="saving"
                            @click="saveDraft"
                        >
                            {{ $t('messages.save_draft') }}
                        </button>
                        <button
                            class="flex-1 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
                            :disabled="saving || !form.name || !form.body"
                            @click="submitForm"
                        >
                            <span v-if="saving">{{ $t('common.sending') }}</span>
                            <span v-else-if="form.send_type === 'now'">{{ $t('messages.send_now') }}</span>
                            <span v-else>{{ $t('common.save') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import axios from 'axios'
import type { Message, SubscriberList } from '@/types'
import UiButton from '@/components/ui/UiButton.vue'
import { useToastStore } from '@/stores/toast'

const { t, locale } = useI18n()
const toast = useToastStore()

const messages = ref<Message[]>([])
const lists = ref<SubscriberList[]>([])
const loading = ref(false)
const saving = ref(false)
const composerOpen = ref(false)
const editingMessage = ref<Message | null>(null)
const search = ref('')
const activeTab = ref('all')
const page = ref(1)
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })

const tabs = [
    { value: 'all',       label: t('common.all') },
    { value: 'draft',     label: t('messages.drafts') },
    { value: 'scheduled', label: t('messages.scheduled') },
    { value: 'sent',      label: t('messages.sent') },
]

const sendTypeOptions: { value: 'now' | 'scheduled' | 'recurring'; label: string }[] = [
    { value: 'now',       label: t('messages.send_now') },
    { value: 'scheduled', label: t('messages.send_later') },
    { value: 'recurring', label: t('messages.recurring') },
]

const defaultForm = () => ({
    name:         '',
    body:         '',
    send_type:    'now' as 'now' | 'scheduled' | 'recurring',
    scheduled_at: '',
    recurrence:   'weekly',
    from_number:  '',
    use_header:   false,
    header:       '',
    list_ids:     [] as number[],
})

const form = ref(defaultForm())

const stats = computed(() => [
    { label: t('messages.total_sent'), value: messages.value.filter(m => m.status === 'sent').length.toLocaleString() },
    { label: t('messages.scheduled'),  value: messages.value.filter(m => m.status === 'scheduled').length.toLocaleString() },
    { label: t('messages.drafts'),     value: messages.value.filter(m => m.status === 'draft').length.toLocaleString() },
])

const estimatedRecipients = computed(() =>
    lists.value
        .filter(l => form.value.list_ids.includes(l.id))
        .reduce((sum, l) => sum + (l.subscribers_count ?? 0), 0)
)

const estimatedCredits = computed(() => estimatedRecipients.value * smsSegments.value)

const smsSegments = computed(() => {
    const len = form.value.body.length
    return len === 0 ? 1 : Math.ceil(len / 160)
})

const minDateTime = computed(() => new Date(Date.now() + 60000).toISOString().slice(0, 16))

async function fetchMessages() {
    loading.value = true
    try {
        const params: Record<string, string | number> = { page: page.value }
        if (activeTab.value !== 'all') params.status = activeTab.value
        if (search.value) params.search = search.value
        const res = await axios.get('/api/v1/messages', { params })
        messages.value = res.data.data
        meta.value = res.data.meta
    } finally {
        loading.value = false
    }
}

async function fetchLists() {
    const res = await axios.get('/api/v1/lists')
    lists.value = Array.isArray(res.data) ? res.data : (res.data.data ?? [])
}

function setTab(value: string) {
    activeTab.value = value
    page.value = 1
    fetchMessages()
}

function changePage(p: number) {
    page.value = p
    fetchMessages()
}

let debounceTimer: ReturnType<typeof setTimeout>
function debouncedFetch() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        page.value = 1
        fetchMessages()
    }, 300)
}

function openComposer(msg?: Message) {
    editingMessage.value = msg ?? null
    form.value = msg ? {
        name:         msg.name,
        body:         msg.body,
        send_type:    msg.send_type,
        scheduled_at: msg.scheduled_at ? msg.scheduled_at.slice(0, 16) : '',
        recurrence:   msg.recurrence ?? 'weekly',
        from_number:  msg.from_number ?? '',
        use_header:   msg.use_header,
        header:       msg.header ?? '',
        list_ids:     msg.lists.map(l => l.id),
    } : defaultForm()
    composerOpen.value = true
}

async function saveDraft() {
    await submit('draft')
}

async function submitForm() {
    await submit(form.value.send_type === 'now' ? 'send' : 'save')
}

async function submit(action: 'draft' | 'save' | 'send') {
    saving.value = true
    try {
        const payload: Record<string, unknown> = {
            name:         form.value.name,
            body:         form.value.body,
            send_type:    action === 'draft' ? 'now' : form.value.send_type,
            from_number:  form.value.from_number || null,
            use_header:   form.value.use_header,
            header:       form.value.use_header ? form.value.header : null,
            list_ids:     form.value.list_ids,
        }
        if (form.value.send_type === 'scheduled' && form.value.scheduled_at) {
            payload.scheduled_at = new Date(form.value.scheduled_at).toISOString()
        }
        if (form.value.send_type === 'recurring') {
            payload.recurrence = form.value.recurrence
        }

        let saved: Message
        if (editingMessage.value) {
            const res = await axios.put(`/api/v1/messages/${editingMessage.value.id}`, payload)
            saved = res.data
        } else {
            const res = await axios.post('/api/v1/messages', payload)
            saved = res.data
        }

        if (action === 'send' && editingMessage.value) {
            await axios.post(`/api/v1/messages/${saved.id}/send`)
        }

        const msg = action === 'draft' ? t('messages.draft_saved') : action === 'send' ? t('messages.send_success') : t('messages.saved')
        toast.success(msg)
        composerOpen.value = false
        fetchMessages()
    } catch {
        toast.error(t('messages.save_error'))
    } finally {
        saving.value = false
    }
}

async function sendNow(msg: Message) {
    try {
        await axios.post(`/api/v1/messages/${msg.id}/send`)
        toast.success(t('messages.send_success'))
        fetchMessages()
    } catch {
        toast.error(t('messages.send_error'))
    }
}

async function deleteMessage(msg: Message) {
    if (!confirm(t('common.confirm_delete'))) return
    try {
        await axios.delete(`/api/v1/messages/${msg.id}`)
        toast.success(t('messages.delete_success'))
        fetchMessages()
    } catch {
        toast.error(t('messages.delete_error'))
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

function formatDate(iso: string | null): string {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString(locale.value, { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(() => {
    fetchMessages()
    fetchLists()
})
</script>
