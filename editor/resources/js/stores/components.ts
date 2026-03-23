import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { ComponentSchemaDTO } from '@/types';
import * as commands from '@/bridge/commands';

export const useComponentsStore = defineStore('components', () => {
    const categories = ref<Record<string, ComponentSchemaDTO[]>>({});
    const schemasCache = ref<Map<string, ComponentSchemaDTO>>(new Map());
    const loaded = ref(false);

    async function fetchAll() {
        const res = await commands.listComponents(true);
        categories.value = res.categories;
        loaded.value = true;

        // Pre-populate cache from grouped response
        for (const group of Object.values(res.categories)) {
            for (const schema of group) {
                schemasCache.value.set(schema.className, schema);
            }
        }
    }

    async function getSchema(className: string): Promise<ComponentSchemaDTO> {
        const cached = schemasCache.value.get(className);
        if (cached) return cached;

        const res = await commands.getComponentSchema(className);
        schemasCache.value.set(className, res.schema);
        return res.schema;
    }

    return {
        categories,
        schemasCache,
        loaded,
        fetchAll,
        getSchema,
    };
});
