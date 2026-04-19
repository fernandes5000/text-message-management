<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ title }}</h1>
        <p class="mt-2 text-gray-500 dark:text-gray-400">Coming soon...</p>
    </div>
</template>

<script setup lang="ts">
const title = import.meta.url.split('/').pop()?.replace('.vue', '').replace('Page', '') ?? 'Page'
</script>
