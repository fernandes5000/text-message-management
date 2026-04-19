<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('polls.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ polls.length }} {{ $t('polls.title').toLowerCase() }}
                </p>
            </div>
            <UiButton @click="openCreate">{{ $t('polls.create') }}</UiButton>
        </div>

        <!-- Polls list -->
        <div v-if="loading" class="flex justify-center py-12">
            <UiSpinner class="h-8 w-8 text-primary-600" />
        </div>

        <div v-else-if="polls.length === 0" class="rounded-xl border-2 border-dashed border-gray-200 py-16 text-center dark:border-gray-700">
            <svg class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('common.no_results') }}</p>
            <UiButton class="mt-4" variant="secondary" size="sm" @click="openCreate">{{ $t('polls.create') }}</UiButton>
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="poll in polls"
                :key="poll.id"
                class="flex cursor-pointer flex-col rounded-xl border bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                @click="openDetail(poll)"
            >
                <!-- Poll header -->
                <div class="mb-3 flex items-start justify-between gap-3">
                    <p class="text-sm font-semibold leading-snug text-gray-900 dark:text-white">{{ poll.question }}</p>
                    <UiBadge :variant="poll.active ? 'green' : 'gray'" class="shrink-0 text-xs">
                        {{ poll.active ? $t('polls.active') : $t('polls.inactive') }}
                    </UiBadge>
                </div>

                <!-- Options preview -->
                <div class="mb-4 space-y-1.5">
                    <div
                        v-for="(option, index) in poll.options"
                        :key="index"
                        class="flex items-center gap-2"
                    >
                        <div class="relative h-2 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div
                                class="absolute left-0 top-0 h-full rounded-full bg-primary-500 transition-all"
                                :style="{ width: getPercent(poll, index) + '%' }"
                            />
                        </div>
                        <span class="w-6 text-right text-xs text-gray-500 dark:text-gray-400">
                            {{ poll.response_counts[index] ?? 0 }}
                        </span>
                        <span class="w-28 truncate text-xs text-gray-600 dark:text-gray-300">{{ option }}</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-auto flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        <strong class="text-gray-700 dark:text-gray-300">{{ poll.total_responses }}</strong>
                        {{ $t('polls.responses') }}
                    </span>
                    <button
                        class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20"
                        @click.stop="confirmDelete(poll)"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="meta && meta.last_page > 1" class="flex justify-center gap-2">
            <UiButton
                v-for="page in meta.last_page"
                :key="page"
                :variant="page === meta.current_page ? 'primary' : 'secondary'"
                size="sm"
                @click="fetchPolls(page)"
            >
                {{ page }}
            </UiButton>
        </div>
    </div>

    <!-- Create Poll Modal -->
    <UiModal v-model="showCreate" :title="$t('polls.create')">
        <form class="space-y-4" @submit.prevent="submitCreate">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('polls.question') }}
                </label>
                <UiInput v-model="form.question" :placeholder="$t('polls.question') + '...'" required />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('polls.options') }}
                </label>
                <div class="space-y-2">
                    <div
                        v-for="(option, index) in form.options"
                        :key="index"
                        class="flex items-center gap-2"
                    >
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                            {{ String.fromCharCode(65 + index) }}
                        </span>
                        <UiInput
                            :model-value="option"
                            :placeholder="`Option ${String.fromCharCode(65 + index)}`"
                            class="flex-1"
                            required
                            @update:model-value="(v) => updateOption(index, v)"
                        />
                        <button
                            v-if="form.options.length > 2"
                            type="button"
                            class="rounded p-1 text-gray-400 hover:text-red-500"
                            @click="removeOption(index)"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <button
                    v-if="form.options.length < 10"
                    type="button"
                    class="mt-2 flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400"
                    @click="addOption"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('polls.add_option') }}
                </button>
            </div>

            <div class="flex justify-end gap-3 border-t pt-4 dark:border-gray-700">
                <UiButton type="button" variant="secondary" @click="showCreate = false">{{ $t('common.cancel') }}</UiButton>
                <UiButton type="submit" :loading="saving">{{ $t('common.create') }}</UiButton>
            </div>
        </form>
    </UiModal>

    <!-- Detail Modal -->
    <UiModal v-if="activePoll" v-model="showDetail" :title="activePoll.question" max-width="lg">
        <div class="space-y-6">
            <!-- Stats row -->
            <div class="grid grid-cols-3 gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700/50">
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ activePoll.total_responses }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('polls.total_responses') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ activePoll.options.length }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('polls.options_count') }}</p>
                </div>
                <div class="text-center">
                    <UiBadge :variant="activePoll.active ? 'green' : 'gray'">
                        {{ activePoll.active ? $t('polls.active') : $t('polls.inactive') }}
                    </UiBadge>
                </div>
            </div>

            <!-- Response breakdown -->
            <div>
                <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $t('polls.response_breakdown') }}</h3>
                <div v-if="activePoll.total_responses === 0" class="py-6 text-center text-sm text-gray-400">
                    {{ $t('polls.no_responses') }}
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="(option, index) in activePoll.options"
                        :key="index"
                    >
                        <div class="mb-1 flex items-center justify-between">
                            <span class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <span class="flex h-5 w-5 items-center justify-center rounded bg-primary-100 text-xs font-bold text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                                    {{ String.fromCharCode(65 + index) }}
                                </span>
                                {{ option }}
                            </span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ getPercent(activePoll, index) }}% ({{ activePoll.response_counts[index] ?? 0 }})
                            </span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div
                                class="h-full rounded-full bg-primary-500 transition-all duration-500"
                                :style="{ width: getPercent(activePoll, index) + '%' }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </UiModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import type { Poll } from '@/types'
import { useToastStore } from '@/stores/toast'
import UiButton from '@/components/ui/UiButton.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'

const { t } = useI18n()
const toast = useToastStore()

const polls = ref<Poll[]>([])
const loading = ref(false)
const saving = ref(false)
const meta = ref<{ current_page: number; last_page: number; total: number } | null>(null)

const showCreate = ref(false)
const showDetail = ref(false)
const activePoll = ref<Poll | null>(null)

const form = ref({
    question: '',
    options: ['', ''],
})

async function fetchPolls(page = 1) {
    loading.value = true
    try {
        const res = await axios.get('/api/v1/polls', { params: { page } })
        polls.value = res.data.data
        meta.value = res.data.meta
    } finally {
        loading.value = false
    }
}

function openCreate() {
    form.value = { question: '', options: ['', ''] }
    showCreate.value = true
}

function addOption() {
    form.value.options.push('')
}

function removeOption(index: number) {
    form.value.options.splice(index, 1)
}

function updateOption(index: number, value: string) {
    form.value.options[index] = value
}

async function submitCreate() {
    if (form.value.options.filter(o => o.trim()).length < 2) return
    saving.value = true
    try {
        const res = await axios.post('/api/v1/polls', {
            question: form.value.question,
            options: form.value.options.filter(o => o.trim()),
        })
        polls.value.unshift(res.data)
        showCreate.value = false
        toast.success(t('polls.create') + ' — OK')
    } catch {
        toast.error('Failed to create poll.')
    } finally {
        saving.value = false
    }
}

async function openDetail(poll: Poll) {
    try {
        const res = await axios.get(`/api/v1/polls/${poll.id}`)
        activePoll.value = res.data
        showDetail.value = true
    } catch {
        toast.error('Failed to load poll.')
    }
}

async function confirmDelete(poll: Poll) {
    if (!confirm(t('polls.delete_confirm'))) return
    try {
        await axios.delete(`/api/v1/polls/${poll.id}`)
        polls.value = polls.value.filter(p => p.id !== poll.id)
        toast.success('Poll deleted.')
    } catch {
        toast.error('Failed to delete poll.')
    }
}

function getPercent(poll: Poll, index: number): number {
    if (poll.total_responses === 0) return 0
    const count = poll.response_counts[index] ?? 0
    return Math.round((count / poll.total_responses) * 100)
}

onMounted(fetchPolls)
</script>
