<template>
    <component
        :is="as"
        :type="as === 'button' ? type : undefined"
        :disabled="disabled || loading"
        :class="classes"
        v-bind="$attrs"
    >
        <UiSpinner v-if="loading" :size="size === 'lg' ? 'md' : 'sm'" class="-ml-0.5 mr-1.5" />
        <slot />
    </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import UiSpinner from './UiSpinner.vue'

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
        size?: 'sm' | 'md' | 'lg'
        as?: string
        type?: 'button' | 'submit' | 'reset'
        disabled?: boolean
        loading?: boolean
    }>(),
    { variant: 'primary', size: 'md', as: 'button', type: 'button', disabled: false, loading: false }
)

const classes = computed(() => [
    'inline-flex items-center justify-center rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2',
    'disabled:cursor-not-allowed disabled:opacity-60',
    {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-5 py-2.5 text-base',
    }[props.size],
    {
        primary:   'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
        secondary: 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
        danger:    'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        ghost:     'text-gray-600 hover:bg-gray-100 focus:ring-gray-400 dark:text-gray-400 dark:hover:bg-gray-800',
    }[props.variant],
])
</script>
