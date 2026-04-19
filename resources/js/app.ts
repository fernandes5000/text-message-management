import './bootstrap.ts'
import '../css/app.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'

import router from './router'
import App from './App.vue'
import { useAuthStore } from './stores/auth'

import en from './locales/en.json'
import ptBR from './locales/pt-BR.json'
import es from './locales/es.json'

const savedLocale = localStorage.getItem('locale') ?? 'en'

const i18n = createI18n({
    legacy: false,
    locale: savedLocale,
    fallbackLocale: 'en',
    messages: { en, 'pt-BR': ptBR, es },
})

const pinia = createPinia()
const app = createApp(App)

app.use(pinia)
app.use(router)
app.use(i18n)

const auth = useAuthStore()
auth.initTheme()

app.mount('#app')
