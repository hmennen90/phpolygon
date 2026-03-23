<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import type { ComponentData, ComponentSchemaDTO } from '@/types';
import { useSceneStore } from '@/stores/scene';
import { useComponentsStore } from '@/stores/components';
import FieldResolver from './fields/FieldResolver.vue';

const props = defineProps<{
    entityName: string;
    componentData: ComponentData;
    schema: ComponentSchemaDTO | null;
}>();

const sceneStore = useSceneStore();
const componentsStore = useComponentsStore();
const collapsed = ref(false);
const resolvedSchema = ref<ComponentSchemaDTO | null>(props.schema);

const shortName = computed(() => {
    const parts = props.componentData._class.split('\\');
    return parts[parts.length - 1];
});

onMounted(async () => {
    if (!resolvedSchema.value) {
        resolvedSchema.value = await componentsStore.getSchema(props.componentData._class);
    }
});

async function removeComponent() {
    await sceneStore.removeComponent(props.entityName, props.componentData._class);
}
</script>

<template>
    <div class="border-b border-editor-border">
        <!-- Header -->
        <div
            class="flex items-center h-6 px-2 bg-editor-active cursor-pointer select-none"
            @click="collapsed = !collapsed"
        >
            <span class="text-xs text-editor-muted mr-1">
                {{ collapsed ? '\u25B8' : '\u25BE' }}
            </span>
            <span class="text-xs font-medium flex-1 truncate">{{ shortName }}</span>
            <button
                class="text-xs text-editor-muted hover:text-red-400 px-1"
                title="Remove component"
                @click.stop="removeComponent"
            >
                X
            </button>
        </div>

        <!-- Properties -->
        <div v-if="!collapsed" class="p-1">
            <template v-if="resolvedSchema">
                <FieldResolver
                    v-for="prop in resolvedSchema.properties"
                    :key="prop.name"
                    :schema="prop"
                    :value="componentData.properties[prop.name]"
                    :entity-name="entityName"
                    :component-class="componentData._class"
                />
            </template>
            <div v-else class="text-xs text-editor-muted px-1">
                Loading schema...
            </div>
        </div>
    </div>
</template>
