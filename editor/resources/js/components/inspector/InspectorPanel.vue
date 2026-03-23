<script setup lang="ts">
import { computed } from 'vue';
import PanelHeader from '@/components/layout/PanelHeader.vue';
import ComponentSection from './ComponentSection.vue';
import AddComponentMenu from './AddComponentMenu.vue';
import { useSelectionStore } from '@/stores/selection';
import { useSceneStore } from '@/stores/scene';
import { useComponentsStore } from '@/stores/components';

const selectionStore = useSelectionStore();
const sceneStore = useSceneStore();
const componentsStore = useComponentsStore();

const selectedEntityData = computed(() => {
    if (!selectionStore.selectedEntity) return null;
    return sceneStore.findEntity(selectionStore.selectedEntity);
});
</script>

<template>
    <div class="flex flex-col h-full bg-editor-panel">
        <PanelHeader :title="selectionStore.selectedEntity ? `Inspector - ${selectionStore.selectedEntity}` : 'Inspector'" />

        <div class="flex-1 overflow-y-auto">
            <template v-if="selectedEntityData">
                <ComponentSection
                    v-for="comp in selectedEntityData.components"
                    :key="comp._class"
                    :entity-name="selectedEntityData.name"
                    :component-data="comp"
                    :schema="componentsStore.schemasCache.get(comp._class) ?? null"
                />

                <div class="p-1 border-t border-editor-border">
                    <AddComponentMenu :entity-name="selectedEntityData.name" />
                </div>
            </template>
            <div v-else class="text-xs text-editor-muted p-2">
                Select an entity to inspect
            </div>
        </div>
    </div>
</template>
