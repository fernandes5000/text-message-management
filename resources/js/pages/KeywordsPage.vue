<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('keywords.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('keywords.subtitle') }}</p>
            </div>
            <UiButton @click="openModal()">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('keywords.create') }}
            </UiButton>
        </div>

        <!-- Tabs + search -->
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

        <!-- Grid -->
        <div v-if="loading" class="flex items-center justify-center py-16">
            <svg class="h-6 w-6 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
        </div>

        <div v-else-if="keywords.length === 0" class="rounded-lg border border-dashed bg-white py-16 text-center dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-400">{{ $t('common.no_results') }}</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="kw in keywords"
                :key="kw.id"
                class="group relative rounded-lg border bg-white p-4 transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
            >
                <!-- Status badge -->
                <span
                    :class="kw.status === 'active'
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                    class="absolute right-3 top-3 rounded-full px-2 py-0.5 text-xs font-medium"
                >
                    {{ kw.status === 'active' ? $t('keywords.active') : $t('keywords.archived') }}
                </span>

                <!-- Keyword name -->
                <div class="mb-3 flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900/30">
                        <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white">{{ kw.name }}</p>
                        <p v-if="kw.number" class="text-xs text-gray-400">{{ kw.number }}</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="mb-3 flex gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span><strong class="text-gray-700 dark:text-gray-200">{{ kw.uses_count.toLocaleString() }}</strong> {{ $t('keywords.uses') }}</span>
                    <span><strong class="text-gray-700 dark:text-gray-200">{{ kw.opt_ins_count.toLocaleString() }}</strong> {{ $t('keywords.opt_ins') }}</span>
                </div>

                <!-- Aliases -->
                <div v-if="kw.aliases.length > 0" class="mb-3 flex flex-wrap gap-1">
                    <span
                        v-for="alias in kw.aliases"
                        :key="alias"
                        class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                    >
                        {{ alias }}
                    </span>
                </div>

                <!-- Workflow summary -->
                <div class="mb-4 text-xs text-gray-400">
                    {{ $t('keywords.workflow_steps', { n: kw.workflow.length }) }}
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <RouterLink
                        :to="`/keywords/${kw.id}`"
                        class="flex-1 rounded-lg border px-3 py-1.5 text-center text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        {{ $t('keywords.edit_workflow') }}
                    </RouterLink>
                    <button
                        class="rounded-lg border px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                        @click="toggleArchive(kw)"
                    >
                        {{ kw.status === 'active' ? $t('keywords.archive') : $t('keywords.unarchive') }}
                    </button>
                    <button
                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-900/40 dark:text-red-400 dark:hover:bg-red-900/20"
                        @click="deleteKeyword(kw)"
                    >
                        {{ $t('common.delete') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $t('common.page_of', { current: meta.current_page, total: meta.last_page }) }}
            </p>
            <div class="flex gap-2">
                <button
                    :disabled="meta.current_page === 1"
                    class="rounded border px-3 py-1 text-sm disabled:opacity-40 dark:border-gray-600 dark:text-gray-300"
                    @click="changePage(meta.current_page - 1)"
                >{{ $t('common.prev') }}</button>
                <button
                    :disabled="meta.current_page === meta.last_page"
                    class="rounded border px-3 py-1 text-sm disabled:opacity-40 dark:border-gray-600 dark:text-gray-300"
                    @click="changePage(meta.current_page + 1)"
                >{{ $t('common.next') }}</button>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body">
        <div
            v-if="modalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="modalOpen = false"
        >
            <div class="w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b px-5 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $t('keywords.create') }}</h2>
                    <button class="rounded p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" @click="modalOpen = false">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('keywords.keyword_label') }}</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. JOIN"
                            class="w-full rounded-lg border px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        />
                        <p class="mt-1 text-xs text-gray-400">Single word, no spaces. Will be uppercased.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('keywords.number') }}</label>
                        <input
                            v-model="form.number"
                            type="text"
                            placeholder="+1 (555) 000-0000"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('keywords.aliases') }}</label>
                        <div class="flex gap-2">
                            <input
                                v-model="aliasInput"
                                type="text"
                                :placeholder="$t('keywords.add_alias')"
                                class="flex-1 rounded-lg border px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                @keydown.enter.prevent="addAlias"
                            />
                            <button
                                class="rounded-lg border px-3 py-2 text-sm dark:border-gray-600 dark:text-gray-300"
                                @click="addAlias"
                            >{{ $t('common.add') }}</button>
                        </div>
                        <div v-if="form.aliases.length > 0" class="mt-2 flex flex-wrap gap-1">
                            <span
                                v-for="(a, i) in form.aliases"
                                :key="i"
                                class="flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-700 dark:text-gray-300"
                            >
                                {{ a }}
                                <button class="text-gray-400 hover:text-red-500" @click="form.aliases.splice(i, 1)">×</button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 border-t px-5 py-4 dark:border-gray-700">
                    <button
                        class="flex-1 rounded-lg border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        @click="modalOpen = false"
                    >{{ $t('common.cancel') }}</button>
                    <button
                        class="flex-1 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
                        :disabled="!form.name.trim() || saving"
                        @click="createKeyword"
                    >
                        {{ saving ? $t('common.saving') : $t('keywords.create') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import type { Keyword } from '@/types'
import UiButton from '@/components/ui/UiButton.vue'
import { useToastStore } from '@/stores/toast'

const { t } = useI18n()
const toast = useToastStore()

const keywords = ref<Keyword[]>([])
const loading = ref(false)
const saving = ref(false)
const modalOpen = ref(false)
const search = ref('')
const activeTab = ref('active')
const page = ref(1)
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const aliasInput = ref('')

const tabs = [
    { value: 'active',   label: t('keywords.active') },
    { value: 'archived', label: t('keywords.archived') },
    { value: '',         label: t('common.all') },
]

const defaultForm = () => ({ name: '', number: '', aliases: [] as string[] })
const form = ref(defaultForm())

async function fetchKeywords() {
    loading.value = true
    try {
        const params: Record<string, string | number> = { page: page.value }
        if (activeTab.value) params.status = activeTab.value
        if (search.value) params.search = search.value
        const res = await axios.get('/api/v1/keywords', { params })
        keywords.value = res.data.data
        meta.value = res.data.meta
    } finally {
        loading.value = false
    }
}

function setTab(value: string) {
    activeTab.value = value
    page.value = 1
    fetchKeywords()
}

function changePage(p: number) {
    page.value = p
    fetchKeywords()
}

let debounceTimer: ReturnType<typeof setTimeout>
function debouncedFetch() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => { page.value = 1; fetchKeywords() }, 300)
}

function openModal() {
    form.value = defaultForm()
    aliasInput.value = ''
    modalOpen.value = true
}

function addAlias() {
    const val = aliasInput.value.trim().toUpperCase()
    if (val && !form.value.aliases.includes(val)) {
        form.value.aliases.push(val)
    }
    aliasInput.value = ''
}

async function createKeyword() {
    saving.value = true
    try {
        await axios.post('/api/v1/keywords', {
            name:    form.value.name.trim(),
            number:  form.value.number || null,
            aliases: form.value.aliases,
        })
        toast.success(t('keywords.created'))
        modalOpen.value = false
        fetchKeywords()
    } catch (e: any) {
        toast.error(e.response?.data?.message ?? t('keywords.create_error'))
    } finally {
        saving.value = false
    }
}

async function toggleArchive(kw: Keyword) {
    try {
        await axios.put(`/api/v1/keywords/${kw.id}`, {
            status: kw.status === 'active' ? 'archived' : 'active',
        })
        toast.success(t('keywords.updated'))
        fetchKeywords()
    } catch {
        toast.error(t('keywords.update_error'))
    }
}

async function deleteKeyword(kw: Keyword) {
    if (!confirm(t('common.confirm_delete'))) return
    try {
        await axios.delete(`/api/v1/keywords/${kw.id}`)
        toast.success(t('keywords.deleted'))
        fetchKeywords()
    } catch {
        toast.error(t('keywords.delete_error'))
    }
}

onMounted(fetchKeywords)
</script>
