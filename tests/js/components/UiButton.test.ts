import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import UiButton from '@/components/ui/UiButton.vue'

describe('UiButton', () => {
    it('renders slot content', () => {
        const wrapper = mount(UiButton, { slots: { default: 'Click me' } })
        expect(wrapper.text()).toContain('Click me')
    })

    it('renders as button element by default', () => {
        const wrapper = mount(UiButton)
        expect(wrapper.element.tagName).toBe('BUTTON')
    })

    it('renders as anchor when as prop is "a"', () => {
        const wrapper = mount(UiButton, { props: { as: 'a' } })
        expect(wrapper.element.tagName).toBe('A')
    })

    it('applies primary variant classes by default', () => {
        const wrapper = mount(UiButton)
        expect(wrapper.classes().join(' ')).toContain('bg-primary-600')
    })

    it('applies secondary variant classes', () => {
        const wrapper = mount(UiButton, { props: { variant: 'secondary' } })
        expect(wrapper.classes().join(' ')).toContain('border')
    })

    it('applies danger variant classes', () => {
        const wrapper = mount(UiButton, { props: { variant: 'danger' } })
        expect(wrapper.classes().join(' ')).toContain('bg-red-600')
    })

    it('is disabled when disabled prop is true', () => {
        const wrapper = mount(UiButton, { props: { disabled: true } })
        expect((wrapper.element as HTMLButtonElement).disabled).toBe(true)
    })

    it('shows spinner when loading', () => {
        const wrapper = mount(UiButton, { props: { loading: true } })
        expect(wrapper.find('[data-testid="spinner"]').exists()).toBe(true)
    })

    it('is disabled when loading', () => {
        const wrapper = mount(UiButton, { props: { loading: true } })
        expect((wrapper.element as HTMLButtonElement).disabled).toBe(true)
    })
})
