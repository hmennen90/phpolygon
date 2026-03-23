<script setup lang="ts">
import PanelHeader from '@/components/layout/PanelHeader.vue';
import { useSceneStore } from '@/stores/scene';
import { useEditorStore } from '@/stores/editor';

const sceneStore = useSceneStore();
const editorStore = useEditorStore();

function togglePlay() {
    if (editorStore.playing) {
        editorStore.stop();
    } else {
        editorStore.play();
    }
}
</script>

<template>
    <div class="flex flex-col h-full bg-editor-panel">
        <PanelHeader :title="sceneStore.name ? `Scene: ${sceneStore.name}` : 'Scene View'" />

        <div class="flex-1 flex flex-col items-center justify-center gap-2">
            <span class="text-xs text-editor-muted">
                {{ sceneStore.name ? `${sceneStore.entityCount} entities` : 'No scene loaded' }}
            </span>

            <button
                v-if="sceneStore.name"
                class="px-4 py-1.5 text-xs font-medium rounded"
                :class="editorStore.playing
                    ? 'bg-red-600 hover:bg-red-700 text-white'
                    : 'bg-green-600 hover:bg-green-700 text-white'"
                @click="togglePlay"
            >
                {{ editorStore.playing ? 'Stop' : 'Play' }}
            </button>
        </div>
    </div>
</template>
