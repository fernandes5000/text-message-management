<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ $t('subscribers.title') }}
            </h1>

            <UiDropdown align="right" width="sm">
                <template #trigger="{ toggle }">
                    <UiButton @click="toggle">
                        <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('subscribers.add') }}
                        <svg class="ml-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </UiButton>
                </template>
                <template #default="{ close }">
                    <div class="py-1">
                        <button
                            class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                            @click="openCreate(); close()"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ $t('subscribers.add_manually') }}
                        </button>
                        <button
                            class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                            @click="showImport = true; close()"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            {{ $t('subscribers.import_csv') }}
                        </button>
                    </div>
                </template>
            </UiDropdown>
        </div>

        <!-- Tabs -->
        <div class="border-b dark:border-gray-700">
            <nav class="-mb-px flex gap-6">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    :class="[
                        'border-b-2 pb-3 text-sm font-medium transition-colors',
                        activeTab === tab.key
                            ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                    ]"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.count"
                        class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                    >
                        {{ tab.count.toLocaleString() }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Subscribers tab -->
        <template v-if="activeTab === 'subscribers'">
            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <UiInput
                    v-model="search"
                    placeholder="Search by name, phone..."
                    class="w-64"
                >
                    <template #leading>
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </template>
                </UiInput>

                <select
                    v-model="statusFilter"
                    class="rounded-lg border px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">All statuses</option>
                    <option value="opted_in">{{ $t('subscribers.opted_in') }}</option>
                    <option value="opted_out">{{ $t('subscribers.opted_out') }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-700 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ $t('common.name') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ $t('common.phone') }}
                            </th>
                            <th class="hidden px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell">
                                {{ $t('common.email') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ $t('common.status') }}
                            </th>
                            <th class="hidden px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 lg:table-cell">
                                Source
                            </th>
                            <th class="hidden px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 lg:table-cell">
                                {{ $t('common.date') }}
                            </th>
                            <th class="w-10 px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-if="loading">
                            <td colspan="7" class="py-12 text-center">
                                <UiSpinner class="mx-auto" size="md" />
                            </td>
                        </tr>
                        <tr v-else-if="!subscribers.length">
                            <td colspan="7" class="py-12 text-center text-sm text-gray-400">
                                {{ $t('common.no_results') }}
                            </td>
                        </tr>
                        <tr
                            v-for="sub in subscribers"
                            :key="sub.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
                        >
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ sub.first_name }} {{ sub.last_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ sub.phone }}</span>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ sub.email ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <UiBadge :variant="sub.status === 'opted_in' ? 'green' : 'red'">
                                    {{ sub.status === 'opted_in' ? $t('subscribers.opted_in') : $t('subscribers.opted_out') }}
                                </UiBadge>
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                <span class="text-sm capitalize text-gray-500 dark:text-gray-400">{{ sub.source }}</span>
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(sub.created_at) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <UiDropdown align="right" width="auto">
                                    <template #trigger="{ toggle }">
                                        <button class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700" @click="toggle">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM12 13.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM12 22a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #default="{ close }">
                                        <div class="py-1">
                                            <button
                                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                                                @click="openEdit(sub); close()"
                                            >
                                                {{ $t('common.edit') }}
                                            </button>
                                            <button
                                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                @click="confirmDelete(sub); close()"
                                            >
                                                {{ $t('common.delete') }}
                                            </button>
                                        </div>
                                    </template>
                                </UiDropdown>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing {{ (meta.current_page - 1) * meta.per_page + 1 }}–{{ Math.min(meta.current_page * meta.per_page, meta.total) }} of {{ meta.total.toLocaleString() }}
                </p>
                <div class="flex gap-1">
                    <UiButton variant="secondary" size="sm" :disabled="meta.current_page === 1" @click="page--">←</UiButton>
                    <UiButton variant="secondary" size="sm" :disabled="meta.current_page === meta.last_page" @click="page++">→</UiButton>
                </div>
            </div>
        </template>

        <!-- Lists tab -->
        <template v-else>
            <div class="flex justify-end">
                <UiButton @click="openCreateList">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('subscribers.create_list') }}
                </UiButton>
            </div>

            <div v-if="loadingLists" class="py-12 text-center">
                <UiSpinner class="mx-auto" size="md" />
            </div>

            <p v-else-if="!lists.length" class="py-12 text-center text-sm text-gray-400">
                {{ $t('common.no_results') }}
            </p>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="list in lists"
                    :key="list.id"
                    class="rounded-xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-medium text-gray-900 dark:text-white">{{ list.name }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ (list.subscribers_count ?? 0).toLocaleString() }} subscribers
                            </p>
                        </div>
                        <div class="ml-2 flex flex-shrink-0 gap-1">
                            <button
                                class="rounded p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                                @click="openEditList(list)"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                class="rounded p-1.5 text-gray-400 hover:bg-red-100 hover:text-red-500 dark:hover:bg-red-900/20"
                                @click="confirmDeleteList(list)"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <UiBadge variant="gray">{{ list.type }}</UiBadge>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Create/Edit Subscriber modal -->
    <UiModal
        v-model="showSubscriberModal"
        :title="editingSubscriber ? 'Edit Subscriber' : 'Add Subscriber'"
        max-width="sm"
    >
        <form class="space-y-4" @submit.prevent="saveSubscriber">
            <div class="grid grid-cols-2 gap-4">
                <UiInput v-model="subscriberForm.first_name" label="First Name" required :error="formErrors.first_name" />
                <UiInput v-model="subscriberForm.last_name" label="Last Name" required :error="formErrors.last_name" />
            </div>
            <UiInput v-model="subscriberForm.phone" :label="$t('common.phone')" type="tel" required :error="formErrors.phone" />
            <UiInput v-model="subscriberForm.email" :label="$t('common.email')" type="email" :error="formErrors.email" />
            <div v-if="editingSubscriber">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('common.status') }}</label>
                <select
                    v-model="subscriberForm.status"
                    class="mt-1.5 block w-full rounded-lg border px-3.5 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="opted_in">{{ $t('subscribers.opted_in') }}</option>
                    <option value="opted_out">{{ $t('subscribers.opted_out') }}</option>
                </select>
            </div>
        </form>
        <template #footer>
            <UiButton variant="secondary" @click="showSubscriberModal = false">{{ $t('common.cancel') }}</UiButton>
            <UiButton :loading="saving" @click="saveSubscriber">{{ $t('common.save') }}</UiButton>
        </template>
    </UiModal>

    <!-- CSV Import modal -->
    <UiModal v-model="showImport" title="Import Subscribers" max-width="sm">
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Upload a CSV with columns:
                <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-gray-800">first_name, last_name, phone, email, source</code>
            </p>
            <label
                :class="[
                    'flex cursor-pointer flex-col items-center gap-2 rounded-lg border-2 border-dashed px-6 py-8 transition hover:border-primary-400',
                    importFile ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/10' : 'border-gray-300 dark:border-gray-600',
                ]"
            >
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ importFile ? importFile.name : 'Click to select a CSV file' }}
                </span>
                <input type="file" accept=".csv,text/csv" class="hidden" @change="onFileChange" />
            </label>
            <div v-if="importResult" class="rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
                <p class="text-sm font-medium text-green-700 dark:text-green-400">
                    Imported: {{ importResult.imported }} | Updated: {{ importResult.updated }}
                </p>
                <ul v-if="importResult.errors.length" class="mt-1 space-y-0.5">
                    <li v-for="(err, i) in importResult.errors" :key="i" class="text-xs text-red-600 dark:text-red-400">{{ err }}</li>
                </ul>
            </div>
        </div>
        <template #footer>
            <UiButton variant="secondary" @click="showImport = false; importFile = null; importResult = null">
                {{ $t('common.close') }}
            </UiButton>
            <UiButton :disabled="!importFile" :loading="importing" @click="runImport">Import</UiButton>
        </template>
    </UiModal>

    <!-- Create/Edit List modal -->
    <UiModal v-model="showListModal" :title="editingList ? 'Edit List' : $t('subscribers.create_list')" max-width="sm">
        <form class="space-y-4" @submit.prevent="saveList">
            <UiInput v-model="listForm.name" :label="$t('common.name')" required :error="listFormErrors.name" />
        </form>
        <template #footer>
            <UiButton variant="secondary" @click="showListModal = false">{{ $t('common.cancel') }}</UiButton>
            <UiButton :loading="savingList" @click="saveList">{{ $t('common.save') }}</UiButton>
        </template>
    </UiModal>

    <!-- Delete Subscriber confirmation -->
    <UiModal v-model="showDeleteModal" title="Delete Subscriber" max-width="sm">
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('common.confirm_delete') }}</p>
        <template #footer>
            <UiButton variant="secondary" @click="showDeleteModal = false">{{ $t('common.cancel') }}</UiButton>
            <UiButton variant="danger" :loading="deleting" @click="deleteSubscriber">{{ $t('common.delete') }}</UiButton>
        </template>
    </UiModal>

    <!-- Delete List confirmation -->
    <UiModal v-model="showDeleteListModal" title="Delete List" max-width="sm">
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('common.confirm_delete') }}</p>
        <template #footer>
            <UiButton variant="secondary" @click="showDeleteListModal = false">{{ $t('common.cancel') }}</UiButton>
            <UiButton variant="danger" :loading="deletingList" @click="deleteList">{{ $t('common.delete') }}</UiButton>
        </template>
    </UiModal>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import UiButton from '@/components/ui/UiButton.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiDropdown from '@/components/ui/UiDropdown.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'
import { useToastStore } from '@/stores/toast'
import type { Subscriber, SubscriberList } from '@/types'

const { t } = useI18n()
const toast = useToastStore()

// ── Tabs ──────────────────────────────────────────────────────────────────
const activeTab = ref<'subscribers' | 'lists'>('subscribers')
const tabs = computed(() => [
    { key: 'subscribers' as const, label: t('subscribers.title'), count: meta.value.total },
    { key: 'lists' as const, label: t('subscribers.lists'), count: lists.value.length },
])

// ── Subscribers list ──────────────────────────────────────────────────────
const subscribers = ref<Subscriber[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0 })
const loading = ref(false)
const search = ref('')
const statusFilter = ref('')
const page = ref(1)

let searchTimer: ReturnType<typeof setTimeout>

watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => { page.value = 1; fetchSubscribers() }, 350)
})

watch([statusFilter, page], () => fetchSubscribers())
onMounted(() => { fetchSubscribers(); fetchLists() })

async function fetchSubscribers() {
    loading.value = true
    try {
        const { data } = await axios.get('/api/v1/subscribers', {
            params: {
                page: page.value,
                search: search.value || undefined,
                status: statusFilter.value || undefined,
            },
        })
        subscribers.value = data.data
        meta.value = data.meta
    } finally {
        loading.value = false
    }
}

// ── Create / Edit subscriber ──────────────────────────────────────────────
const showSubscriberModal = ref(false)
const editingSubscriber = ref<Subscriber | null>(null)
const subscriberForm = ref({ first_name: '', last_name: '', phone: '', email: '', status: 'opted_in' })
const formErrors = ref<Record<string, string>>({})
const saving = ref(false)

function openCreate() {
    editingSubscriber.value = null
    subscriberForm.value = { first_name: '', last_name: '', phone: '', email: '', status: 'opted_in' }
    formErrors.value = {}
    showSubscriberModal.value = true
}

function openEdit(sub: Subscriber) {
    editingSubscriber.value = sub
    subscriberForm.value = {
        first_name: sub.first_name,
        last_name: sub.last_name,
        phone: sub.phone,
        email: sub.email ?? '',
        status: sub.status,
    }
    formErrors.value = {}
    showSubscriberModal.value = true
}

async function saveSubscriber() {
    saving.value = true
    formErrors.value = {}
    try {
        if (editingSubscriber.value) {
            await axios.put(`/api/v1/subscribers/${editingSubscriber.value.id}`, subscriberForm.value)
            toast.success('Subscriber updated.')
        } else {
            await axios.post('/api/v1/subscribers', subscriberForm.value)
            toast.success('Subscriber added.')
        }
        showSubscriberModal.value = false
        fetchSubscribers()
    } catch (e: unknown) {
        const err = e as { response?: { data?: { errors?: Record<string, string[]> } } }
        const errors = err.response?.data?.errors ?? {}
        formErrors.value = Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0]]))
    } finally {
        saving.value = false
    }
}

// ── Delete subscriber ─────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const deletingTarget = ref<Subscriber | null>(null)
const deleting = ref(false)

function confirmDelete(sub: Subscriber) {
    deletingTarget.value = sub
    showDeleteModal.value = true
}

async function deleteSubscriber() {
    if (!deletingTarget.value) return
    deleting.value = true
    try {
        await axios.delete(`/api/v1/subscribers/${deletingTarget.value.id}`)
        toast.success('Subscriber deleted.')
        showDeleteModal.value = false
        fetchSubscribers()
    } finally {
        deleting.value = false
    }
}

// ── CSV Import ────────────────────────────────────────────────────────────
const showImport = ref(false)
const importFile = ref<File | null>(null)
const importResult = ref<{ imported: number; updated: number; errors: string[] } | null>(null)
const importing = ref(false)

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement
    importFile.value = input.files?.[0] ?? null
    importResult.value = null
}

async function runImport() {
    if (!importFile.value) return
    importing.value = true
    const formData = new FormData()
    formData.append('file', importFile.value)
    try {
        const { data } = await axios.post('/api/v1/subscribers/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        importResult.value = data
        toast.success(`Imported ${data.imported} subscribers.`)
        fetchSubscribers()
    } catch {
        toast.error('Import failed. Please check the file format.')
    } finally {
        importing.value = false
    }
}

// ── Lists ─────────────────────────────────────────────────────────────────
const lists = ref<SubscriberList[]>([])
const loadingLists = ref(false)

async function fetchLists() {
    loadingLists.value = true
    try {
        const { data } = await axios.get('/api/v1/lists')
        lists.value = data
    } finally {
        loadingLists.value = false
    }
}

const showListModal = ref(false)
const editingList = ref<SubscriberList | null>(null)
const listForm = ref({ name: '', type: 'manual' })
const listFormErrors = ref<Record<string, string>>({})
const savingList = ref(false)

function openCreateList() {
    editingList.value = null
    listForm.value = { name: '', type: 'manual' }
    listFormErrors.value = {}
    showListModal.value = true
}

function openEditList(list: SubscriberList) {
    editingList.value = list
    listForm.value = { name: list.name, type: list.type }
    listFormErrors.value = {}
    showListModal.value = true
}

async function saveList() {
    savingList.value = true
    listFormErrors.value = {}
    try {
        if (editingList.value) {
            await axios.put(`/api/v1/lists/${editingList.value.id}`, listForm.value)
            toast.success('List updated.')
        } else {
            await axios.post('/api/v1/lists', listForm.value)
            toast.success('List created.')
        }
        showListModal.value = false
        fetchLists()
    } catch (e: unknown) {
        const err = e as { response?: { data?: { errors?: Record<string, string[]> } } }
        const errors = err.response?.data?.errors ?? {}
        listFormErrors.value = Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0]]))
    } finally {
        savingList.value = false
    }
}

const showDeleteListModal = ref(false)
const deletingListTarget = ref<SubscriberList | null>(null)
const deletingList = ref(false)

function confirmDeleteList(list: SubscriberList) {
    deletingListTarget.value = list
    showDeleteListModal.value = true
}

async function deleteList() {
    if (!deletingListTarget.value) return
    deletingList.value = true
    try {
        await axios.delete(`/api/v1/lists/${deletingListTarget.value.id}`)
        toast.success('List deleted.')
        showDeleteListModal.value = false
        fetchLists()
    } finally {
        deletingList.value = false
    }
}

// ── Utilities ─────────────────────────────────────────────────────────────
function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>
