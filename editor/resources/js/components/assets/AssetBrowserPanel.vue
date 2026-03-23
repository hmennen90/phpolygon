<script setup lang="ts">
import PanelHeader from '@/components/layout/PanelHeader.vue';
import { useProjectStore } from '@/stores/project';

const projectStore = useProjectStore();
</script>

<template>
    <div class="flex flex-col h-full bg-editor-panel">
        <PanelHeader title="Asset Browser" />
        <div class="flex-1 flex items-center justify-center">
            <span v-if="!projectStore.opened" class="text-xs text-editor-muted">
                Open a project to browse assets
            </span>
            <span v-else class="text-xs text-editor-muted">
                {{ projectStore.assetsPath }}
            </span>
        </div>
    </div>
</template>
