<template>
    <div v-if="loading" class="flex items-center justify-center py-24">
        <svg class="h-6 w-6 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
    </div>

    <div v-else-if="keyword" class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <RouterLink
                to="/keywords"
                class="rounded-lg border p-2 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </RouterLink>

            <div class="flex-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ keyword.name }}</h1>
                    <span
                        :class="keyword.status === 'active'
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                    >
                        {{ keyword.status === 'active' ? $t('keywords.active') : $t('keywords.archived') }}
                    </span>
                </div>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    {{ keyword.number || 'No number set' }}
                    <span v-if="keyword.aliases.length > 0" class="ml-2">
                        · Aliases: {{ keyword.aliases.join(', ') }}
                    </span>
                </p>
            </div>

            <div class="flex gap-2">
                <button
                    class="rounded-lg border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    @click="openSettings = true"
                >
                    {{ $t('keywords.settings') }}
                </button>
                <UiButton :disabled="saving" @click="saveWorkflow">
                    {{ saving ? $t('common.saving') : $t('keywords.save_workflow') }}
                </UiButton>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-lg border bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('keywords.uses') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ keyword.uses_count.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('keywords.opt_ins') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ keyword.opt_ins_count.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $t('keywords.lists') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ (keyword.lists ?? []).length }}</p>
            </div>
        </div>

        <!-- Workflow Builder -->
        <div class="rounded-lg border bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b px-5 py-4 dark:border-gray-700">
                <h2 class="font-semibold text-gray-900 dark:text-white">{{ $t('keywords.workflow') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('keywords.workflow_subtitle') }}
                </p>
            </div>

            <div class="p-5">
                <!-- Trigger (always present) -->
                <div class="flex items-start gap-3 mb-2">
                    <div class="flex flex-col items-center">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-white text-xs font-bold">T</div>
                        <div v-if="workflow.length > 0" class="mt-1 w-px flex-1 bg-gray-200 dark:bg-gray-700" style="min-height:24px" />
                    </div>
                    <div class="flex-1 rounded-lg border border-primary-200 bg-primary-50 p-3 dark:border-primary-800 dark:bg-primary-900/20">
                        <p class="text-sm font-medium text-primary-800 dark:text-primary-300">{{ $t('keywords.triggers') }}: Inbound SMS</p>
                        <p class="mt-0.5 text-xs text-primary-600 dark:text-primary-400">{{ $t('keywords.trigger_description', { keyword: keyword.name, number: keyword.number || $t('keywords.your_number') }) }}</p>
                    </div>
                </div>

                <!-- Workflow steps -->
                <div v-for="(step, idx) in workflow" :key="idx" class="flex items-start gap-3">
                    <!-- Connector -->
                    <div class="flex flex-col items-center">
                        <div class="w-px flex-none bg-gray-200 dark:bg-gray-700" style="height:12px" />
                        <div
                            :class="stepIconClass(step.type)"
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-white text-xs font-bold"
                        >
                            {{ idx + 1 }}
                        </div>
                        <div v-if="idx < workflow.length - 1" class="mt-1 w-px flex-1 bg-gray-200 dark:bg-gray-700" style="min-height:24px" />
                    </div>

                    <!-- Step card -->
                    <div class="mb-2 flex-1 rounded-lg border bg-white p-4 dark:border-gray-600 dark:bg-gray-700/50">
                        <div class="mb-3 flex items-center justify-between">
                            <select
                                v-model="step.type"
                                class="rounded border px-2 py-1 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-700 dark:text-white"
                                @change="onStepTypeChange(step)"
                            >
                                <option value="send_message">{{ $t('keywords.step_send_message') }}</option>
                                <option value="add_to_list">{{ $t('keywords.step_add_to_list') }}</option>
                                <option value="collect_info">{{ $t('keywords.step_collect_info') }}</option>
                            </select>
                            <button
                                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-red-500 dark:hover:bg-gray-600"
                                @click="removeStep(idx)"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- send_message config -->
                        <div v-if="step.type === 'send_message'">
                            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('keywords.step_send_message') }}</label>
                            <textarea
                                :value="step.config.message as string"
                                rows="3"
                                maxlength="320"
                                @input="step.config.message = ($event.target as HTMLTextAreaElement).value"
                                :placeholder="$t('keywords.default_message_placeholder')"
                                class="w-full resize-none rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                            />
                            <p class="mt-1 text-right text-xs text-gray-400">{{ (step.config.message as string ?? '').length }}/320</p>
                        </div>

                        <!-- add_to_list config -->
                        <div v-else-if="step.type === 'add_to_list'">
                            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('keywords.select_list') }}</label>
                            <select
                                :value="step.config.list_id as string | number | null"
                                @change="step.config.list_id = ($event.target as HTMLSelectElement).value || null"
                                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option :value="null">— choose a list —</option>
                                <option v-for="list in lists" :key="list.id" :value="list.id">
                                    {{ list.name }} ({{ (list.subscribers_count ?? 0).toLocaleString() }})
                                </option>
                            </select>
                        </div>

                        <!-- collect_info config -->
                        <div v-else-if="step.type === 'collect_info'">
                            <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('keywords.question_to_ask') }}</label>
                            <input
                                v-model="step.config.question"
                                type="text"
                                :placeholder="$t('keywords.default_question_placeholder')"
                                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-700 dark:text-white"
                            />
                            <label class="mb-1 mt-2 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ $t('keywords.save_as') }}</label>
                            <select
                                v-model="step.config.field"
                                class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="first_name">{{ $t('keywords.save_as_first_name') }}</option>
                                <option value="last_name">{{ $t('keywords.save_as_last_name') }}</option>
                                <option value="email">{{ $t('keywords.save_as_email') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Add step button -->
                <div class="flex items-start gap-3">
                    <div class="flex flex-col items-center">
                        <div class="w-px bg-gray-200 dark:bg-gray-700" style="height:12px" />
                    </div>
                    <button
                        class="flex items-center gap-2 rounded-lg border-2 border-dashed px-4 py-2 text-sm text-gray-500 hover:border-primary-400 hover:text-primary-600 dark:border-gray-600 dark:text-gray-400 dark:hover:border-primary-500 dark:hover:text-primary-400"
                        @click="addStep"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('keywords.add_step') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings slide-over -->
    <Teleport to="body">
        <div
            v-if="openSettings"
            class="fixed inset-0 z-50 flex items-center justify-end bg-black/50"
            @click.self="openSettings = false"
        >
            <div class="flex h-full w-full max-w-sm flex-col bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b px-5 py-4 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">{{ $t('keywords.settings') }}</h2>
                    <button class="rounded p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" @click="openSettings = false">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto p-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('keywords.keyword_name') }}</label>
                        <input
                            v-model="settings.name"
                            type="text"
                            class="w-full rounded-lg border px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('keywords.number') }}</label>
                        <input
                            v-model="settings.number"
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
                                @keydown.enter.prevent="addSettingsAlias"
                            />
                            <button class="rounded-lg border px-3 py-2 text-sm dark:border-gray-600 dark:text-gray-300" @click="addSettingsAlias">{{ $t('common.add') }}</button>
                        </div>
                        <div v-if="settings.aliases.length > 0" class="mt-2 flex flex-wrap gap-1">
                            <span
                                v-for="(a, i) in settings.aliases"
                                :key="i"
                                class="flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-700 dark:text-gray-300"
                            >
                                {{ a }}
                                <button class="text-gray-400 hover:text-red-500" @click="settings.aliases.splice(i, 1)">×</button>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('keywords.lists') }}</label>
                        <div class="space-y-1.5 rounded-lg border p-3 dark:border-gray-600">
                            <label
                                v-for="list in lists"
                                :key="list.id"
                                class="flex cursor-pointer items-center gap-2 rounded px-1 py-0.5 hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <input
                                    type="checkbox"
                                    :value="list.id"
                                    v-model="settings.list_ids"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ list.name }}</span>
                                <span class="text-xs text-gray-400">{{ (list.subscribers_count ?? 0).toLocaleString() }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="border-t px-5 py-4 dark:border-gray-700">
                    <button
                        class="w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
                        :disabled="saving"
                        @click="saveSettings"
                    >
                        {{ saving ? $t('common.saving') : $t('common.save') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import type { Keyword, SubscriberList } from '@/types'
import UiButton from '@/components/ui/UiButton.vue'
import { useToastStore } from '@/stores/toast'

const route = useRoute()
const { t } = useI18n()
const toast = useToastStore()

const keyword = ref<Keyword | null>(null)
const loading = ref(true)
const saving = ref(false)
const lists = ref<SubscriberList[]>([])
const openSettings = ref(false)
const aliasInput = ref('')

interface WorkflowStep {
    type: 'send_message' | 'add_to_list' | 'collect_info'
    config: Record<string, unknown>
}

const workflow = ref<WorkflowStep[]>([])

const settings = ref({
    name:     '',
    number:   '',
    aliases:  [] as string[],
    list_ids: [] as number[],
})

async function fetchKeyword() {
    loading.value = true
    try {
        const res = await axios.get(`/api/v1/keywords/${route.params.id}`)
        keyword.value = res.data
        workflow.value = [...(res.data.workflow ?? [])]
        settings.value = {
            name:     res.data.name,
            number:   res.data.number ?? '',
            aliases:  [...(res.data.aliases ?? [])],
            list_ids: (res.data.lists ?? []).map((l: SubscriberList) => l.id),
        }
    } finally {
        loading.value = false
    }
}

async function fetchLists() {
    const res = await axios.get('/api/v1/lists')
    lists.value = Array.isArray(res.data) ? res.data : (res.data.data ?? [])
}

function addStep() {
    workflow.value.push({ type: 'send_message', config: { message: '' } })
}

function removeStep(idx: number) {
    workflow.value.splice(idx, 1)
}

function onStepTypeChange(step: WorkflowStep) {
    if (step.type === 'send_message') step.config = { message: '' }
    else if (step.type === 'add_to_list') step.config = { list_id: null }
    else if (step.type === 'collect_info') step.config = { question: '', field: 'first_name' }
}

async function saveWorkflow() {
    saving.value = true
    try {
        const res = await axios.put(`/api/v1/keywords/${keyword.value!.id}`, {
            workflow: workflow.value,
        })
        keyword.value = res.data
        toast.success(t('keywords.workflow_saved'))
    } catch {
        toast.error(t('keywords.workflow_save_error'))
    } finally {
        saving.value = false
    }
}

function addSettingsAlias() {
    const val = aliasInput.value.trim().toUpperCase()
    if (val && !settings.value.aliases.includes(val)) {
        settings.value.aliases.push(val)
    }
    aliasInput.value = ''
}

async function saveSettings() {
    saving.value = true
    try {
        const res = await axios.put(`/api/v1/keywords/${keyword.value!.id}`, {
            name:     settings.value.name,
            number:   settings.value.number || null,
            aliases:  settings.value.aliases,
            list_ids: settings.value.list_ids,
        })
        keyword.value = res.data
        toast.success(t('keywords.settings_saved'))
        openSettings.value = false
    } catch {
        toast.error(t('keywords.settings_save_error'))
    } finally {
        saving.value = false
    }
}

function stepIconClass(type: string): string {
    const map: Record<string, string> = {
        send_message: 'bg-blue-500',
        add_to_list:  'bg-green-500',
        collect_info: 'bg-amber-500',
    }
    return map[type] ?? 'bg-gray-400'
}

onMounted(() => {
    fetchKeyword()
    fetchLists()
})
</script>
