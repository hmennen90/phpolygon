<script setup lang="ts">
defineProps<{
    title: string;
}>();
</script>

<template>
    <div class="flex items-center px-2 h-7 bg-editor-active text-xs font-medium border-b border-editor-border shrink-0">
        {{ title }}
    </div>
</template>
