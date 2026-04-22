import { setActivePinia, createPinia } from 'pinia'
import { beforeEach, afterEach, describe, it, expect, vi } from 'vitest'
import { useToastStore } from '@/stores/toast'

describe('useToastStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.useFakeTimers()
    })

    afterEach(() => {
        vi.useRealTimers()
    })

    it('starts with no toasts', () => {
        const store = useToastStore()
        expect(store.toasts).toHaveLength(0)
    })

    it('adds a toast', () => {
        const store = useToastStore()
        store.add({ message: 'Hello', type: 'success' })
        expect(store.toasts).toHaveLength(1)
        expect(store.toasts[0].message).toBe('Hello')
        expect(store.toasts[0].type).toBe('success')
    })

    it('assigns a unique id to each toast', () => {
        const store = useToastStore()
        store.add({ message: 'First', type: 'info' })
        store.add({ message: 'Second', type: 'info' })
        expect(store.toasts[0].id).not.toBe(store.toasts[1].id)
    })

    it('removes a toast by id', () => {
        const store = useToastStore()
        store.add({ message: 'Hello', type: 'success' })
        const id = store.toasts[0].id
        store.remove(id)
        expect(store.toasts).toHaveLength(0)
    })

    it('auto-removes toast after default duration', () => {
        const store = useToastStore()
        store.add({ message: 'Hello', type: 'success' })
        expect(store.toasts).toHaveLength(1)
        vi.advanceTimersByTime(4000)
        expect(store.toasts).toHaveLength(0)
    })

    it('auto-removes toast after custom duration', () => {
        const store = useToastStore()
        store.add({ message: 'Hello', type: 'success', duration: 1500 })
        vi.advanceTimersByTime(1499)
        expect(store.toasts).toHaveLength(1)
        vi.advanceTimersByTime(1)
        expect(store.toasts).toHaveLength(0)
    })

    it('adds success toast via helper', () => {
        const store = useToastStore()
        store.success('Saved!')
        expect(store.toasts[0].type).toBe('success')
        expect(store.toasts[0].message).toBe('Saved!')
    })

    it('adds error toast via helper', () => {
        const store = useToastStore()
        store.error('Something went wrong')
        expect(store.toasts[0].type).toBe('error')
    })

    it('adds warning toast via helper', () => {
        const store = useToastStore()
        store.warning('Be careful')
        expect(store.toasts[0].type).toBe('warning')
    })

    it('adds info toast via helper', () => {
        const store = useToastStore()
        store.info('FYI')
        expect(store.toasts[0].type).toBe('info')
    })
})
