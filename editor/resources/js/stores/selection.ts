import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useSelectionStore = defineStore('selection', () => {
    const selectedEntity = ref<string | null>(null);
    const selectedComponent = ref<string | null>(null);

    function selectEntity(name: string | null) {
        selectedEntity.value = name;
        selectedComponent.value = null;
    }

    function selectComponent(className: string | null) {
        selectedComponent.value = className;
    }

    function clearSelection() {
        selectedEntity.value = null;
        selectedComponent.value = null;
    }

    return {
        selectedEntity,
        selectedComponent,
        selectEntity,
        selectComponent,
        clearSelection,
    };
});
