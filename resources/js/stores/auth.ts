import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import type { User, Organization } from '@/types'

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null)
    const organization = ref<Organization | null>(null)
    const organizations = ref<Organization[]>([])

    const isAuthenticated = computed(() => user.value !== null)

    async function login(email: string, password: string, remember = false) {
        await axios.get('/sanctum/csrf-cookie')
        await axios.post('/api/v1/auth/login', { email, password, remember })
        await fetchUser()
    }

    async function logout() {
        await axios.post('/api/v1/auth/logout')
        user.value = null
        organization.value = null
        organizations.value = []
    }

    async function fetchUser() {
        const { data } = await axios.get('/api/v1/auth/me')
        user.value = data.user
        organization.value = data.organization
        organizations.value = data.organizations
    }

    async function switchOrganization(id: number) {
        await axios.post(`/api/v1/accounts/switch/${id}`)
        await fetchUser()
    }

    function setTheme(theme: 'light' | 'dark') {
        if (user.value) {
            user.value.theme = theme
        }
        applyTheme(theme)
    }

    function applyTheme(theme: 'light' | 'dark') {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
        localStorage.setItem('theme', theme)
    }

    function initTheme() {
        const stored = localStorage.getItem('theme') as 'light' | 'dark' | null
        const preferred = stored ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
        applyTheme(preferred)
    }

    return {
        user,
        organization,
        organizations,
        isAuthenticated,
        login,
        logout,
        fetchUser,
        switchOrganization,
        setTheme,
        initTheme,
    }
})
