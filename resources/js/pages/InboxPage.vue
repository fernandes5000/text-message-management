<template>
    <div class="flex h-full gap-0 -m-6 overflow-hidden">
        <!-- Sidebar: conversation list -->
        <aside class="flex w-80 flex-shrink-0 flex-col border-r bg-white dark:border-gray-700 dark:bg-gray-800">
            <!-- Header -->
            <div class="border-b px-4 py-3 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white">{{ $t('inbox.title') }}</h1>
                    <span
                        v-if="unreadCount > 0"
                        class="rounded-full bg-primary-600 px-2 py-0.5 text-xs font-medium text-white"
                    >{{ unreadCount }}</span>
                </div>

                <!-- Search -->
                <div class="relative mt-2">
                    <svg class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('inbox.search')"
                        class="w-full rounded-md border bg-gray-50 py-1.5 pl-8 pr-3 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                        @input="debouncedFetch"
                    />
                </div>

                <!-- Filter tabs -->
                <div class="mt-2 flex gap-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        :class="[
                            'flex-1 rounded px-2 py-1 text-xs font-medium transition-colors',
                            activeTab === tab.value
                                ? 'bg-primary-600 text-white'
                                : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700',
                        ]"
                        @click="setTab(tab.value)"
                    >{{ tab.label }}</button>
                </div>
            </div>

            <!-- List -->
            <div class="flex-1 overflow-y-auto">
                <div v-if="loadingList" class="flex justify-center py-8">
                    <svg class="h-5 w-5 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                </div>

                <p v-else-if="conversations.length === 0" class="py-10 text-center text-xs text-gray-400">
                    {{ $t('common.no_results') }}
                </p>

                <button
                    v-for="conv in conversations"
                    :key="conv.id"
                    :class="[
                        'w-full border-b px-4 py-3 text-left transition-colors dark:border-gray-700',
                        selectedId === conv.id
                            ? 'bg-primary-50 dark:bg-primary-900/20'
                            : 'hover:bg-gray-50 dark:hover:bg-gray-700/40',
                    ]"
                    @click="selectConversation(conv)"
                >
                    <div class="flex items-start gap-2">
                        <!-- Avatar -->
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-medium text-gray-600 dark:bg-gray-600 dark:text-gray-200">
                            {{ initials(conv) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between">
                                <p
                                    :class="conv.unread ? 'font-semibold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-300'"
                                    class="truncate text-xs"
                                >
                                    {{ subscriberName(conv) }}
                                </p>
                                <span class="ml-1 flex-shrink-0 text-[10px] text-gray-400">{{ relativeTime(conv.last_message_at) }}</span>
                            </div>
                            <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">{{ conv.last_message }}</p>
                        </div>
                        <span v-if="conv.unread" class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-primary-600" />
                    </div>
                </button>

                <!-- Load more -->
                <div v-if="meta.current_page < meta.last_page" class="p-3">
                    <button
                        class="w-full rounded-lg border py-2 text-xs text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                        @click="loadMore"
                    >Load more</button>
                </div>
            </div>
        </aside>

        <!-- Main: conversation view -->
        <div class="flex flex-1 flex-col bg-gray-50 dark:bg-gray-950">
            <!-- Empty state -->
            <div v-if="!selectedConversation" class="flex flex-1 items-center justify-center">
                <div class="text-center text-gray-400">
                    <svg class="mx-auto mb-3 h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <p class="text-sm">Select a conversation to start</p>
                </div>
            </div>

            <template v-else>
                <!-- Conv header -->
                <div class="flex items-center justify-between border-b bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-sm font-medium text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                            {{ initials(selectedConversation) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ subscriberName(selectedConversation) }}</p>
                            <p class="text-xs text-gray-400">{{ selectedConversation.subscriber?.phone }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="selectedConversation.status === 'open'"
                            class="rounded-lg border px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-800"
                            @click="markDone"
                        >{{ $t('inbox.mark_done') }}</button>
                        <button
                            class="rounded-lg border px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-800"
                            @click="markUnread"
                        >{{ $t('inbox.mark_unread') }}</button>
                        <span
                            :class="selectedConversation.status === 'open'
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                            class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                        >{{ selectedConversation.status }}</span>
                    </div>
                </div>

                <!-- Messages -->
                <div ref="messagesEl" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
                    <div v-if="loadingMessages" class="flex justify-center py-8">
                        <svg class="h-5 w-5 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                    </div>

                    <template v-else>
                        <div
                            v-for="msg in activeMessages"
                            :key="msg.id"
                            :class="msg.direction === 'outbound' ? 'flex justify-end' : 'flex justify-start'"
                        >
                            <div
                                :class="msg.direction === 'outbound'
                                    ? 'rounded-2xl rounded-br-sm bg-primary-600 text-white'
                                    : 'rounded-2xl rounded-bl-sm bg-white text-gray-800 shadow-sm dark:bg-gray-800 dark:text-gray-200'"
                                class="max-w-xs px-4 py-2.5 text-sm lg:max-w-md"
                            >
                                <p>{{ msg.body }}</p>
                                <p
                                    :class="msg.direction === 'outbound' ? 'text-primary-200' : 'text-gray-400'"
                                    class="mt-1 text-right text-[10px]"
                                >{{ formatTime(msg.sent_at) }}</p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Reply bar -->
                <div class="border-t bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-end gap-3">
                        <textarea
                            v-model="replyBody"
                            rows="2"
                            :placeholder="$t('inbox.reply')"
                            class="flex-1 resize-none rounded-xl border bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
                            @keydown.enter.ctrl.prevent="sendReply"
                            @keydown.meta.enter.prevent="sendReply"
                        />
                        <button
                            :disabled="!replyBody.trim() || sendingReply"
                            class="flex-shrink-0 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
                            @click="sendReply"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1 text-[10px] text-gray-400">Ctrl+Enter to send</p>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import type { Conversation, ConversationMessage } from '@/types'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const { t } = useI18n()
const auth = useAuthStore()
const toast = useToastStore()

// State
const conversations = ref<Conversation[]>([])
const selectedId = ref<number | null>(null)
const selectedConversation = ref<Conversation | null>(null)
const activeMessages = ref<ConversationMessage[]>([])
const loadingList = ref(false)
const loadingMessages = ref(false)
const sendingReply = ref(false)
const replyBody = ref('')
const search = ref('')
const activeTab = ref('open')
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const messagesEl = ref<HTMLElement | null>(null)

const tabs = [
    { value: 'open', label: t('inbox.open') },
    { value: '',     label: 'All' },
    { value: 'done', label: t('inbox.done') },
]

const unreadCount = computed(() => conversations.value.filter(c => c.unread).length)

// Fetch conversations
async function fetchConversations(append = false) {
    loadingList.value = !append
    try {
        const params: Record<string, string | number> = {
            page: append ? meta.value.current_page + 1 : 1,
        }
        if (activeTab.value) params.status = activeTab.value
        if (search.value) params.search = search.value

        const res = await axios.get('/api/v1/conversations', { params })
        conversations.value = append
            ? [...conversations.value, ...res.data.data]
            : res.data.data
        meta.value = res.data.meta
    } finally {
        loadingList.value = false
    }
}

function setTab(value: string) {
    activeTab.value = value
    selectedId.value = null
    selectedConversation.value = null
    activeMessages.value = []
    fetchConversations()
}

function loadMore() {
    fetchConversations(true)
}

let debounceTimer: ReturnType<typeof setTimeout>
function debouncedFetch() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => fetchConversations(), 300)
}

// Select a conversation
async function selectConversation(conv: Conversation) {
    selectedId.value = conv.id
    selectedConversation.value = conv
    loadingMessages.value = true
    activeMessages.value = []

    try {
        const res = await axios.get(`/api/v1/conversations/${conv.id}`)
        selectedConversation.value = res.data
        activeMessages.value = res.data.messages ?? []

        // Mark as read in the sidebar
        const idx = conversations.value.findIndex(c => c.id === conv.id)
        if (idx !== -1) conversations.value[idx].unread = false
    } finally {
        loadingMessages.value = false
        scrollToBottom()
    }
}

// Reply
async function sendReply() {
    if (!replyBody.value.trim() || !selectedConversation.value) return
    sendingReply.value = true

    try {
        const res = await axios.post(`/api/v1/conversations/${selectedConversation.value.id}/reply`, {
            body: replyBody.value.trim(),
        })
        activeMessages.value.push(res.data)
        replyBody.value = ''
        scrollToBottom()

        // Update sidebar preview
        const idx = conversations.value.findIndex(c => c.id === selectedConversation.value!.id)
        if (idx !== -1) {
            conversations.value[idx].last_message = res.data.body
            conversations.value[idx].last_message_at = res.data.sent_at
        }
    } catch {
        toast.error('Failed to send reply.')
    } finally {
        sendingReply.value = false
    }
}

// Actions
async function markDone() {
    if (!selectedConversation.value) return
    const res = await axios.patch(`/api/v1/conversations/${selectedConversation.value.id}/done`)
    selectedConversation.value = { ...selectedConversation.value, ...res.data }
    const idx = conversations.value.findIndex(c => c.id === selectedConversation.value!.id)
    if (idx !== -1) conversations.value[idx].status = 'done'
}

async function markUnread() {
    if (!selectedConversation.value) return
    const res = await axios.patch(`/api/v1/conversations/${selectedConversation.value.id}/unread`)
    selectedConversation.value = { ...selectedConversation.value, ...res.data }
    const idx = conversations.value.findIndex(c => c.id === selectedConversation.value!.id)
    if (idx !== -1) conversations.value[idx].unread = true
}

// Helpers
function subscriberName(conv: Conversation): string {
    if (!conv.subscriber) return conv.number
    return `${conv.subscriber.first_name} ${conv.subscriber.last_name}`.trim() || conv.number
}

function initials(conv: Conversation): string {
    const name = subscriberName(conv)
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

function relativeTime(iso: string | null): string {
    if (!iso) return ''
    const diff = Date.now() - new Date(iso).getTime()
    const mins = Math.floor(diff / 60000)
    if (mins < 1) return 'now'
    if (mins < 60) return `${mins}m`
    const hrs = Math.floor(mins / 60)
    if (hrs < 24) return `${hrs}h`
    return `${Math.floor(hrs / 24)}d`
}

function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesEl.value) {
            messagesEl.value.scrollTop = messagesEl.value.scrollHeight
        }
    })
}

// Echo: real-time updates
let echoChannel: any = null

function subscribeEcho() {
    const orgId = auth.organization?.id
    if (!orgId || !window.Echo) return

    echoChannel = window.Echo.channel(`organization.${orgId}`)
        .listen('.message.new', (data: any) => {
            const { message, conversation } = data

            // Update sidebar conversation
            const idx = conversations.value.findIndex(c => c.id === conversation.id)
            if (idx !== -1) {
                conversations.value[idx] = {
                    ...conversations.value[idx],
                    last_message:    conversation.last_message,
                    last_message_at: conversation.last_message_at,
                    unread:          selectedId.value !== conversation.id,
                }
                // Bubble to top
                const updated = conversations.value.splice(idx, 1)[0]
                conversations.value.unshift(updated)
            }

            // Append to open conversation
            if (selectedId.value === conversation.id) {
                activeMessages.value.push(message)
                scrollToBottom()
            }
        })
}

onMounted(() => {
    fetchConversations()
    subscribeEcho()
})

onUnmounted(() => {
    if (echoChannel) {
        window.Echo.leaveChannel(`organization.${auth.organization?.id}`)
    }
})
</script>
