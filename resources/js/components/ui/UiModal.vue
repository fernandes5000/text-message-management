<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @mousedown.self="closeOnBackdrop && $emit('update:modelValue', false)"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" />

                <!-- Panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div
                        v-if="modelValue"
                        :class="['relative z-10 w-full rounded-xl bg-white shadow-xl dark:bg-gray-900', maxWidthClass]"
                    >
                        <!-- Header -->
                        <div v-if="title || $slots.header" class="flex items-center justify-between border-b px-6 py-4 dark:border-gray-800">
                            <slot name="header">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ title }}</h2>
                            </slot>
                            <button
                                v-if="showClose"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800"
                                @click="$emit('update:modelValue', false)"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-5">
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div v-if="$slots.footer" class="flex items-center justify-end gap-3 border-t px-6 py-4 dark:border-gray-800">
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        title?: string
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl'
        closeOnBackdrop?: boolean
        showClose?: boolean
    }>(),
    { maxWidth: 'md', closeOnBackdrop: true, showClose: true }
)

defineEmits<{ 'update:modelValue': [value: boolean] }>()

const maxWidthClass = computed(() => ({
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
}[props.maxWidth]))
</script>
