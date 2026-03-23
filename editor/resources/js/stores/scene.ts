import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { EntityNode } from '@/types';
import * as commands from '@/bridge/commands';

export const useSceneStore = defineStore('scene', () => {
    const name = ref<string>('');
    const entities = ref<EntityNode[]>([]);
    const dirty = ref(false);
    const loading = ref(false);

    const entityCount = computed(() => {
        let count = 0;
        const walk = (nodes: EntityNode[]) => {
            for (const n of nodes) {
                count++;
                walk(n.children);
            }
        };
        walk(entities.value);
        return count;
    });

    function findEntity(name: string, nodes?: EntityNode[]): EntityNode | null {
        for (const n of nodes ?? entities.value) {
            if (n.name === name) return n;
            const found = findEntity(name, n.children);
            if (found) return found;
        }
        return null;
    }

    async function load(sceneName: string) {
        loading.value = true;
        try {
            const data = await commands.loadScene(sceneName);
            name.value = data.name;
            entities.value = data.entities;
            dirty.value = false;
        } finally {
            loading.value = false;
        }
    }

    async function save() {
        await commands.saveScene();
        dirty.value = false;
    }

    async function refreshHierarchy() {
        const data = await commands.getEntityHierarchy();
        entities.value = data.entities;
    }

    async function createEntity(entityName: string, parent: string | null = null) {
        await commands.createEntity(entityName, parent);
        await refreshHierarchy();
        dirty.value = true;
    }

    async function deleteEntity(entityName: string) {
        await commands.deleteEntity(entityName);
        await refreshHierarchy();
        dirty.value = true;
    }

    async function addComponent(entityName: string, component: string) {
        await commands.addComponent(entityName, component);
        await refreshHierarchy();
        dirty.value = true;
    }

    async function removeComponent(entityName: string, component: string) {
        await commands.removeComponent(entityName, component);
        await refreshHierarchy();
        dirty.value = true;
    }

    async function updateProperty(
        entityName: string,
        component: string,
        property: string,
        value: unknown,
    ) {
        await commands.updateProperty(entityName, component, property, value);
        // Optimistic: update local state
        const entity = findEntity(entityName);
        if (entity) {
            const comp = entity.components.find((c) => c._class === component);
            if (comp) {
                comp.properties[property] = value;
            }
        }
        dirty.value = true;
    }

    async function renameEntity(oldName: string, newName: string) {
        await commands.renameEntity(oldName, newName);
        await refreshHierarchy();
        dirty.value = true;
    }

    async function undoAction() {
        await commands.undo();
        await refreshHierarchy();
    }

    async function redoAction() {
        await commands.redo();
        await refreshHierarchy();
    }

    return {
        name,
        entities,
        dirty,
        loading,
        entityCount,
        findEntity,
        load,
        save,
        refreshHierarchy,
        createEntity,
        deleteEntity,
        addComponent,
        removeComponent,
        updateProperty,
        renameEntity,
        undoAction,
        redoAction,
    };
});
