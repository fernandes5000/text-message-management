<template>
    <div ref="container" class="relative">
        <div @click="toggle">
            <slot name="trigger" :open="isOpen" />
        </div>

        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                :class="[
                    'absolute z-50 mt-1 rounded-lg border bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800',
                    alignClass,
                    widthClass,
                ]"
            >
                <slot :close="close" />
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = withDefaults(
    defineProps<{
        align?: 'left' | 'right'
        width?: 'auto' | 'sm' | 'md' | 'lg'
    }>(),
    { align: 'right', width: 'md' }
)

const isOpen = ref(false)
const container = ref<HTMLElement | null>(null)

const alignClass = computed(() => props.align === 'right' ? 'right-0' : 'left-0')
const widthClass = computed(() => ({
    auto: 'w-auto',
    sm:   'w-40',
    md:   'w-56',
    lg:   'w-72',
}[props.width]))

function toggle() { isOpen.value = !isOpen.value }
function close() { isOpen.value = false }

function handleClickOutside(e: MouseEvent) {
    if (container.value && !container.value.contains(e.target as Node)) {
        close()
    }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))

defineExpose({ close })
</script>
