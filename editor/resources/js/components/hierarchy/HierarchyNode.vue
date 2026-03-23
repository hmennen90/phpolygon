<script setup lang="ts">
import { ref } from 'vue';
import type { EntityNode } from '@/types';
import { useSelectionStore } from '@/stores/selection';

const props = defineProps<{
    node: EntityNode;
    depth: number;
}>();

const selectionStore = useSelectionStore();
const expanded = ref(props.node.expanded ?? true);

function toggle() {
    expanded.value = !expanded.value;
}

function select() {
    selectionStore.selectEntity(props.node.name);
}

const isSelected = () => selectionStore.selectedEntity === props.node.name;
</script>

<template>
    <div>
        <div
            class="flex items-center h-6 cursor-pointer text-xs select-none"
            :class="{
                'bg-editor-accent/20 border-l-2 border-editor-accent': isSelected(),
                'hover:bg-editor-hover': !isSelected(),
            }"
            :style="{ paddingLeft: `${depth * 16 + 4}px` }"
            @click="select"
        >
            <!-- Expand arrow -->
            <span
                v-if="node.children.length > 0"
                class="w-4 text-center text-editor-muted cursor-pointer shrink-0"
                @click.stop="toggle"
            >
                {{ expanded ? '\u25BE' : '\u25B8' }}
            </span>
            <span v-else class="w-4 shrink-0" />

            <span class="truncate">{{ node.name }}</span>
        </div>

        <!-- Children -->
        <template v-if="expanded && node.children.length > 0">
            <HierarchyNode
                v-for="child in node.children"
                :key="child.name"
                :node="child"
                :depth="depth + 1"
            />
        </template>
    </div>
</template>
