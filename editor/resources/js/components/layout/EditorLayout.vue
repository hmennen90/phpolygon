<script setup lang="ts">
import Toolbar from './Toolbar.vue';
import HierarchyPanel from '@/components/hierarchy/HierarchyPanel.vue';
import InspectorPanel from '@/components/inspector/InspectorPanel.vue';
import SceneViewPanel from '@/components/scene/SceneViewPanel.vue';
import AssetBrowserPanel from '@/components/assets/AssetBrowserPanel.vue';
</script>

<template>
    <div class="h-screen w-screen flex flex-col bg-editor-bg text-editor-text overflow-hidden">
        <!-- Toolbar -->
        <Toolbar />

        <!-- Main grid: hierarchy | scene | inspector -->
        <div class="flex-1 grid grid-cols-[250px_1fr_300px] grid-rows-[1fr_200px] min-h-0">
            <!-- Hierarchy (left, spans both rows) -->
            <div class="row-span-2 border-r border-editor-border overflow-hidden">
                <HierarchyPanel />
            </div>

            <!-- Scene view (center top) -->
            <div class="border-b border-editor-border overflow-hidden">
                <SceneViewPanel />
            </div>

            <!-- Inspector (right, spans both rows) -->
            <div class="row-span-2 border-l border-editor-border overflow-hidden">
                <InspectorPanel />
            </div>

            <!-- Asset browser (center bottom) -->
            <div class="overflow-hidden">
                <AssetBrowserPanel />
            </div>
        </div>
    </div>
</template>
