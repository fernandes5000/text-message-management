<template>
    <div :class="['space-y-1.5', $attrs.class]">
        <label
            v-if="label"
            :for="inputId"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            {{ label }}
            <span v-if="required" class="ml-0.5 text-red-500">*</span>
        </label>

        <div class="relative">
            <div v-if="$slots.leading" class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <slot name="leading" />
            </div>

            <input
                :id="inputId"
                v-bind="{ ...$attrs, class: undefined }"
                :value="modelValue"
                :class="inputClasses"
                @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            />

            <div v-if="$slots.trailing" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <slot name="trailing" />
            </div>
        </div>

        <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
        <p v-else-if="hint" class="text-sm text-gray-500 dark:text-gray-400">{{ hint }}</p>
    </div>
</template>

<script setup lang="ts">
import { computed, useId } from 'vue'

defineOptions({ inheritAttrs: false })

const props = withDefaults(
    defineProps<{
        modelValue?: string
        label?: string
        error?: string
        hint?: string
        required?: boolean
        id?: string
    }>(),
    { modelValue: '', required: false }
)

defineEmits<{ 'update:modelValue': [value: string] }>()

const inputId = computed(() => props.id ?? useId())

const inputClasses = computed(() => [
    'block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-sm outline-none transition',
    'placeholder:text-gray-400 dark:placeholder:text-gray-500',
    props.error
        ? 'border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-red-700'
        : 'border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700',
    'bg-white text-gray-900 dark:bg-gray-800 dark:text-white',
])
</script>
