<template>
    <div ref="container" class="relative inline-block">
        <slot name="trigger" :open="isOpen" :toggle="toggle" />

        <Teleport to="body">
            <div
                v-if="isOpen"
                ref="panelRef"
                :style="panelStyle"
                :class="[
                    'fixed z-[9999] rounded-lg border bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800',
                    widthClass,
                ]"
            >
                <slot :close="close" />
            </div>
        </Teleport>
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
const panelRef = ref<HTMLElement | null>(null)
const panelStyle = ref<Record<string, string>>({})

const widthClass = computed(() => ({
    auto: 'w-auto min-w-[8rem]',
    sm:   'w-40',
    md:   'w-56',
    lg:   'w-72',
}[props.width]))

function calcPosition() {
    if (!container.value) return
    const rect = container.value.getBoundingClientRect()
    const style: Record<string, string> = {
        top: `${rect.bottom + 4}px`,
    }
    if (props.align === 'right') {
        style.right = `${window.innerWidth - rect.right}px`
    } else {
        style.left = `${rect.left}px`
    }
    panelStyle.value = style
}

function toggle() {
    if (!isOpen.value) calcPosition()
    isOpen.value = !isOpen.value
}

function close() {
    isOpen.value = false
}

function handleClickOutside(e: MouseEvent) {
    const target = e.target as Node
    const insideTrigger = container.value?.contains(target) ?? false
    const insidePanel = panelRef.value?.contains(target) ?? false
    if (!insideTrigger && !insidePanel) close()
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))

defineExpose({ close, toggle })
</script>
