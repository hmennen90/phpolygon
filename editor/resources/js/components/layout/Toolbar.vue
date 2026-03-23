<script setup lang="ts">
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

async function save() {
    await sceneStore.save();
}

async function undo() {
    await sceneStore.undoAction();
}

async function redo() {
    await sceneStore.redoAction();
}
</script>

<template>
    <div class="flex items-center gap-1 px-2 h-9 bg-editor-panel border-b border-editor-border shrink-0">
        <!-- Play / Stop -->
        <button
            class="px-3 py-1 text-xs font-medium rounded"
            :class="editorStore.playing
                ? 'bg-red-600 hover:bg-red-700 text-white'
                : 'bg-green-600 hover:bg-green-700 text-white'"
            @click="togglePlay"
        >
            {{ editorStore.playing ? 'Stop' : 'Play' }}
        </button>

        <div class="w-px h-5 bg-editor-border mx-1" />

        <!-- Save -->
        <button
            class="px-2 py-1 text-xs rounded hover:bg-editor-hover active:bg-editor-active"
            @click="save"
        >
            Save
        </button>

        <!-- Undo / Redo -->
        <button
            class="px-2 py-1 text-xs rounded hover:bg-editor-hover active:bg-editor-active"
            @click="undo"
        >
            Undo
        </button>
        <button
            class="px-2 py-1 text-xs rounded hover:bg-editor-hover active:bg-editor-active"
            @click="redo"
        >
            Redo
        </button>

        <div class="w-px h-5 bg-editor-border mx-1" />

        <!-- Scene name + dirty indicator -->
        <span class="text-xs text-editor-muted">
            {{ sceneStore.name || 'No scene' }}
        </span>
        <span
            v-if="sceneStore.dirty"
            class="w-2 h-2 rounded-full bg-editor-accent shrink-0"
            title="Unsaved changes"
        />

        <div class="flex-1" />

        <span class="text-xs text-editor-muted">PHPolygon Editor</span>
    </div>
</template>
