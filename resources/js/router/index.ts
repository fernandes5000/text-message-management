import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('@/pages/auth/LoginPage.vue'),
            meta: { guest: true },
        },
        {
            path: '/',
            component: () => import('@/layouts/AppLayout.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    name: 'dashboard',
                    component: () => import('@/pages/DashboardPage.vue'),
                },
                {
                    path: 'inbox',
                    name: 'inbox',
                    component: () => import('@/pages/InboxPage.vue'),
                },
                {
                    path: 'subscribers',
                    name: 'subscribers',
                    component: () => import('@/pages/SubscribersPage.vue'),
                },
                {
                    path: 'messages',
                    name: 'messages',
                    component: () => import('@/pages/MessagesPage.vue'),
                },
                {
                    path: 'messages/create',
                    name: 'messages.create',
                    component: () => import('@/pages/CreateMessagePage.vue'),
                },
                {
                    path: 'keywords',
                    name: 'keywords',
                    component: () => import('@/pages/KeywordsPage.vue'),
                },
                {
                    path: 'keywords/:id',
                    name: 'keywords.show',
                    component: () => import('@/pages/KeywordDetailPage.vue'),
                },
                {
                    path: 'polls',
                    name: 'polls',
                    component: () => import('@/pages/PollsPage.vue'),
                },
                {
                    path: 'integrations',
                    name: 'integrations',
                    component: () => import('@/pages/IntegrationsPage.vue'),
                },
            ],
        },
        {
            path: '/:pathMatch(.*)*',
            redirect: '/',
        },
    ],
})

router.beforeEach(async (to) => {
    const auth = useAuthStore()

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        try {
            await auth.fetchUser()
        } catch {
            return { name: 'login' }
        }
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' }
    }
})

export default router
