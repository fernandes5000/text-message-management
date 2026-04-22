<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('settings.account') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('settings.subtitle') }}</p>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                :class="[
                    'px-4 py-2 text-sm font-medium transition-colors',
                    activeTab === tab.key
                        ? 'border-b-2 border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                ]"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Account Settings tab -->
        <div v-if="activeTab === 'account'" class="max-w-lg">
            <div class="rounded-lg border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-5 font-semibold text-gray-900 dark:text-white">{{ $t('settings.organization_info') }}</h2>

                <form class="space-y-4" @submit.prevent="saveSettings">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('common.name') }}
                        </label>
                        <UiInput v-model="form.name" :placeholder="$t('common.name')" required />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('settings.default_number') }}
                        </label>
                        <UiInput v-model="form.default_number" :placeholder="$t('settings.default_number_placeholder')" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('common.credits') }}
                        </label>
                        <p class="mt-1 text-2xl font-bold text-primary-600">{{ org?.credits?.toLocaleString() ?? '—' }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('settings.credits_note') }}</p>
                    </div>

                    <div class="pt-2">
                        <UiButton type="submit" :disabled="savingSettings">
                            {{ savingSettings ? $t('common.saving') : $t('common.save') }}
                        </UiButton>
                    </div>
                </form>
            </div>

            <!-- Sub-accounts -->
            <div class="mt-6 rounded-lg border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 font-semibold text-gray-900 dark:text-white">{{ $t('settings.sub_accounts') }}</h2>
                <div v-if="subAccounts.length === 0" class="text-sm text-gray-400">{{ $t('settings.no_sub_accounts') }}</div>
                <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                    <li
                        v-for="sub in subAccounts"
                        :key="sub.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ sub.name }}</p>
                            <p class="text-xs text-gray-400">{{ sub.default_number || '—' }}</p>
                        </div>
                        <span class="text-xs text-gray-400">{{ (sub.credits ?? 0).toLocaleString() }} {{ $t('common.credits') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Team Members tab -->
        <div v-if="activeTab === 'members'">
            <div class="rounded-lg border bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">{{ $t('settings.members') }}</h2>
                    <UiButton size="sm" @click="showInviteModal = true">{{ $t('settings.invite_member') }}</UiButton>
                </div>

                <div v-if="loadingMembers" class="p-6 text-center text-sm text-gray-400">{{ $t('common.loading') }}</div>
                <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                    <li v-if="members.length === 0" class="px-5 py-4 text-sm text-gray-400">{{ $t('common.no_results') }}</li>
                    <li
                        v-for="member in members"
                        :key="member.id"
                        class="flex items-center gap-3 px-5 py-4"
                    >
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                            {{ initials(member.name) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ member.name }}</p>
                            <p class="truncate text-xs text-gray-400">{{ member.email }}</p>
                        </div>
                        <span :class="roleBadge(member.role)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                            {{ $t(`settings.role_${member.role}`) }}
                        </span>
                        <button
                            v-if="member.id !== authUser?.id"
                            class="ml-2 rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20"
                            :title="$t('settings.remove_member')"
                            @click="confirmRemove(member)"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Invite Modal -->
        <UiModal v-model="showInviteModal" :title="$t('settings.invite_member')" maxWidth="sm">
            <form class="space-y-4" @submit.prevent="inviteMember">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('common.name') }}</label>
                    <UiInput v-model="inviteForm.name" :placeholder="$t('common.name')" required />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('common.email') }}</label>
                    <UiInput v-model="inviteForm.email" type="email" :placeholder="$t('common.email')" required />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('settings.role') }}</label>
                    <select
                        v-model="inviteForm.role"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="member">{{ $t('settings.role_member') }}</option>
                        <option value="admin">{{ $t('settings.role_admin') }}</option>
                    </select>
                </div>
                <p class="text-xs text-gray-400">{{ $t('settings.invite_note') }}</p>
                <div class="flex gap-3 justify-end pt-1">
                    <UiButton variant="secondary" type="button" @click="showInviteModal = false">{{ $t('common.cancel') }}</UiButton>
                    <UiButton type="submit" :disabled="inviting">
                        {{ inviting ? $t('common.sending') : $t('settings.send_invite') }}
                    </UiButton>
                </div>
            </form>
        </UiModal>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import UiButton from '@/components/ui/UiButton.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const { t } = useI18n()
const auth  = useAuthStore()
const toast = useToastStore()

const org          = ref(auth.organization)
const subAccounts  = ref<{ id: number; name: string; default_number: string | null; credits: number; parent_id: number | null }[]>([])
const members      = ref<{ id: number; name: string; email: string; role: string }[]>([])
const loadingMembers = ref(false)
const savingSettings = ref(false)
const showInviteModal = ref(false)
const inviting       = ref(false)

const authUser = computed(() => auth.user)

const activeTab = ref<'account' | 'members'>('account')
const tabs = computed(() => [
    { key: 'account' as const, label: t('settings.account_tab') },
    { key: 'members' as const, label: t('settings.members') },
])

const form = ref({
    name:           org.value?.name ?? '',
    default_number: org.value?.default_number ?? '',
})

const inviteForm = ref({ name: '', email: '', role: 'member' })

function initials(name: string): string {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
}

function roleBadge(role: string): string {
    return role === 'admin'
        ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
}

async function loadSettings() {
    const { data } = await axios.get('/api/v1/account/settings')
    org.value = data
    form.value.name           = data.name
    form.value.default_number = data.default_number ?? ''
}

async function loadSubAccounts() {
    const { data } = await axios.get('/api/v1/accounts')
    subAccounts.value = (data.organizations ?? []).filter((o: any) => o.is_sub_account)
}

async function loadMembers() {
    loadingMembers.value = true
    try {
        const { data } = await axios.get('/api/v1/account/members')
        members.value = data
    } finally {
        loadingMembers.value = false
    }
}

async function saveSettings() {
    savingSettings.value = true
    try {
        const { data } = await axios.put('/api/v1/account/settings', form.value)
        org.value = data
        auth.organization = data
        toast.success(t('settings.saved'))
    } catch {
        toast.error(t('settings.save_error'))
    } finally {
        savingSettings.value = false
    }
}

async function inviteMember() {
    inviting.value = true
    try {
        const { data } = await axios.post('/api/v1/account/members', inviteForm.value)
        members.value.push(data)
        showInviteModal.value = false
        inviteForm.value = { name: '', email: '', role: 'member' }
        toast.success(t('settings.member_invited'))
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? t('settings.invite_error'))
    } finally {
        inviting.value = false
    }
}

async function confirmRemove(member: { id: number; name: string }) {
    if (! confirm(t('settings.confirm_remove', { name: member.name }))) return
    try {
        await axios.delete(`/api/v1/account/members/${member.id}`)
        members.value = members.value.filter(m => m.id !== member.id)
        toast.success(t('settings.member_removed'))
    } catch {
        toast.error(t('settings.remove_error'))
    }
}

onMounted(async () => {
    await Promise.all([loadSettings(), loadSubAccounts(), loadMembers()])
})
</script>
