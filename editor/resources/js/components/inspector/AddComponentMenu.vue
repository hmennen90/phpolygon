<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useComponentsStore } from '@/stores/components';
import { useSceneStore } from '@/stores/scene';

const props = defineProps<{
    entityName: string;
}>();

const componentsStore = useComponentsStore();
const sceneStore = useSceneStore();
const open = ref(false);

onMounted(async () => {
    if (!componentsStore.loaded) {
        await componentsStore.fetchAll();
    }
});

async function add(className: string) {
    await sceneStore.addComponent(props.entityName, className);
    open.value = false;
}
</script>

<template>
    <div class="relative">
        <button
            class="w-full text-xs py-1 rounded hover:bg-editor-hover active:bg-editor-active"
            @click="open = !open"
        >
            + Add Component
        </button>

        <div
            v-if="open"
            class="absolute bottom-full left-0 w-full max-h-60 overflow-y-auto bg-editor-panel border border-editor-border rounded shadow-lg z-10 mb-1"
        >
            <template v-for="(components, category) in componentsStore.categories" :key="category">
                <div class="px-2 py-1 text-xs font-medium text-editor-muted bg-editor-active sticky top-0">
                    {{ category }}
                </div>
                <button
                    v-for="comp in components"
                    :key="comp.className"
                    class="w-full text-left px-2 py-1 text-xs hover:bg-editor-hover"
                    @click="add(comp.className)"
                >
                    {{ comp.shortName }}
                </button>
            </template>

            <div
                v-if="Object.keys(componentsStore.categories).length === 0"
                class="px-2 py-1 text-xs text-editor-muted"
            >
                No components available
            </div>
        </div>
    </div>
</template>
