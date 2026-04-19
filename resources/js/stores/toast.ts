import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface Toast {
    id: number
    message: string
    type: ToastType
    duration: number
}

let nextId = 0

export const useToastStore = defineStore('toast', () => {
    const toasts = ref<Toast[]>([])

    function add(payload: { message: string; type: ToastType; duration?: number }) {
        const id = ++nextId
        const duration = payload.duration ?? 4000
        toasts.value.push({ id, message: payload.message, type: payload.type, duration })
        setTimeout(() => remove(id), duration)
    }

    function remove(id: number) {
        const index = toasts.value.findIndex((t) => t.id === id)
        if (index !== -1) toasts.value.splice(index, 1)
    }

    function success(message: string, duration?: number) { add({ message, type: 'success', duration }) }
    function error(message: string, duration?: number) { add({ message, type: 'error', duration }) }
    function warning(message: string, duration?: number) { add({ message, type: 'warning', duration }) }
    function info(message: string, duration?: number) { add({ message, type: 'info', duration }) }

    return { toasts, add, remove, success, error, warning, info }
})
