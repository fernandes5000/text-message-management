<template>
    <span :class="classes">
        <slot />
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
    defineProps<{ variant?: 'green' | 'red' | 'yellow' | 'purple' | 'gray' }>(),
    { variant: 'gray' }
)

const classes = computed(() => [
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
    {
        green:  'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400',
        red:    'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400',
        yellow: 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400',
        purple: 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400',
        gray:   'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    }[props.variant],
])
</script>
