import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import UiBadge from '@/components/ui/UiBadge.vue'

describe('UiBadge', () => {
    it('renders slot content', () => {
        const wrapper = mount(UiBadge, { slots: { default: 'Active' } })
        expect(wrapper.text()).toBe('Active')
    })

    it('applies green variant', () => {
        const wrapper = mount(UiBadge, { props: { variant: 'green' } })
        expect(wrapper.classes().join(' ')).toContain('text-green')
    })

    it('applies red variant', () => {
        const wrapper = mount(UiBadge, { props: { variant: 'red' } })
        expect(wrapper.classes().join(' ')).toContain('text-red')
    })

    it('applies gray variant by default', () => {
        const wrapper = mount(UiBadge)
        expect(wrapper.classes().join(' ')).toContain('text-gray')
    })

    it('renders as span element', () => {
        const wrapper = mount(UiBadge)
        expect(wrapper.element.tagName).toBe('SPAN')
    })
})
