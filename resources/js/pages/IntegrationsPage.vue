<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('integrations.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Connect your favourite tools to automate workflows and sync contacts.
            </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <UiSpinner class="h-8 w-8 text-primary-600" />
        </div>

        <!-- Grid -->
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="integration in integrations"
                :key="integration.id"
                class="flex flex-col rounded-xl border bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <!-- Logo + name row -->
                <div class="mb-4 flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl text-white shadow-sm"
                        :class="logoClass(integration.type)"
                    >
                        <component :is="logoIcon(integration.type)" class="h-7 w-7" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            {{ $t(`integrations.names.${integration.type}`) }}
                        </h3>
                        <UiBadge
                            :variant="integration.status === 'connected' ? 'green' : 'gray'"
                            class="mt-0.5 text-xs"
                        >
                            {{ $t(`integrations.${integration.status}`) }}
                        </UiBadge>
                    </div>
                </div>

                <!-- Description -->
                <p class="mb-4 flex-1 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                    {{ $t(`integrations.descriptions.${integration.type}`) }}
                </p>

                <!-- Actions -->
                <div class="flex items-center gap-2 border-t border-gray-100 pt-4 dark:border-gray-700">
                    <template v-if="integration.status === 'disconnected'">
                        <UiButton
                            size="sm"
                            :loading="toggling === integration.id"
                            @click="connect(integration)"
                        >
                            {{ $t('integrations.connect') }}
                        </UiButton>
                    </template>
                    <template v-else>
                        <UiButton
                            size="sm"
                            variant="secondary"
                            @click="openConfigure(integration)"
                        >
                            {{ $t('integrations.configure') }}
                        </UiButton>
                        <UiButton
                            size="sm"
                            variant="danger"
                            :loading="toggling === integration.id"
                            @click="disconnect(integration)"
                        >
                            {{ $t('integrations.disconnect') }}
                        </UiButton>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Configure Modal (demo) -->
    <UiModal v-if="configureTarget" v-model="showConfigure" :title="$t(`integrations.names.${configureTarget.type}`)">
        <div class="space-y-4">
            <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium text-green-700 dark:text-green-300">Integration is active and syncing.</p>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">API Key</label>
                <UiInput model-value="••••••••••••••••••••••••••••••••" disabled />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sync Direction</label>
                <select class="block w-full rounded-md border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option>Two-way sync</option>
                    <option>Import only</option>
                    <option>Export only</option>
                </select>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-700/50 dark:bg-amber-900/20">
                <p class="text-xs text-amber-700 dark:text-amber-300">
                    <strong>Demo:</strong> Configuration changes are not persisted in this demo environment.
                </p>
            </div>

            <div class="flex justify-end gap-3 border-t pt-4 dark:border-gray-700">
                <UiButton variant="secondary" @click="showConfigure = false">{{ $t('common.close') }}</UiButton>
                <UiButton @click="showConfigure = false">{{ $t('common.save') }}</UiButton>
            </div>
        </div>
    </UiModal>
</template>

<script setup lang="ts">
import { ref, onMounted, defineComponent, h } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import type { Integration } from '@/types'
import { useToastStore } from '@/stores/toast'
import UiButton from '@/components/ui/UiButton.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSpinner from '@/components/ui/UiSpinner.vue'

const { t } = useI18n()
const toast = useToastStore()

const integrations = ref<Integration[]>([])
const loading = ref(false)
const toggling = ref<number | null>(null)
const showConfigure = ref(false)
const configureTarget = ref<Integration | null>(null)

async function fetchIntegrations() {
    loading.value = true
    try {
        const res = await axios.get('/api/v1/integrations')
        integrations.value = Array.isArray(res.data) ? res.data : (res.data.data ?? [])
    } finally {
        loading.value = false
    }
}

async function connect(integration: Integration) {
    toggling.value = integration.id
    try {
        const res = await axios.post(`/api/v1/integrations/${integration.id}/connect`)
        updateInList(res.data)
        toast.success(`${t(`integrations.names.${integration.type}`)} connected.`)
    } catch {
        toast.error('Failed to connect.')
    } finally {
        toggling.value = null
    }
}

async function disconnect(integration: Integration) {
    toggling.value = integration.id
    try {
        const res = await axios.post(`/api/v1/integrations/${integration.id}/disconnect`)
        updateInList(res.data)
        showConfigure.value = false
        toast.success(`${t(`integrations.names.${integration.type}`)} disconnected.`)
    } catch {
        toast.error('Failed to disconnect.')
    } finally {
        toggling.value = null
    }
}

function updateInList(updated: Integration) {
    const idx = integrations.value.findIndex(i => i.id === updated.id)
    if (idx !== -1) integrations.value[idx] = updated
}

function openConfigure(integration: Integration) {
    configureTarget.value = integration
    showConfigure.value = true
}

// Logo colours per integration type
function logoClass(type: string): string {
    const map: Record<string, string> = {
        planning_center: 'bg-blue-600',
        salesforce:      'bg-sky-500',
        zapier:          'bg-orange-500',
        mailchimp:       'bg-yellow-500',
        hubspot:         'bg-orange-600',
    }
    return map[type] ?? 'bg-gray-500'
}

// Simple SVG icon component per integration (letter-based for demo)
function logoIcon(type: string) {
    const letters: Record<string, string> = {
        planning_center: 'PC',
        salesforce: 'SF',
        zapier: 'Za',
        mailchimp: 'Mc',
        hubspot: 'HS',
    }
    const letter = letters[type] ?? type.slice(0, 2).toUpperCase()

    return defineComponent({
        render() {
            return h('span', {
                class: 'text-xs font-bold text-white select-none',
            }, letter)
        },
    })
}

onMounted(fetchIntegrations)
</script>
