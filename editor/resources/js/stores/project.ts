import { defineStore } from 'pinia';
import { ref } from 'vue';
import { get } from '@/bridge/api';
import type { ProjectConfig } from '@/types';

export const useProjectStore = defineStore('project', () => {
    const name = ref('');
    const projectDir = ref('');
    const scenesPath = ref('');
    const assetsPath = ref('');
    const entryScene = ref('');
    const scenes = ref<string[]>([]);
    const opened = ref(false);

    async function openProject(dir?: string) {
        const config = await get<ProjectConfig>(
            dir ? `/project?dir=${encodeURIComponent(dir)}` : '/project',
        );

        name.value = config.name;
        projectDir.value = config.projectDir;
        scenesPath.value = config.scenesPath;
        assetsPath.value = config.assetsPath;
        entryScene.value = config.entryScene;
        scenes.value = config.scenes;
        opened.value = true;
    }

    return {
        name,
        projectDir,
        scenesPath,
        assetsPath,
        entryScene,
        scenes,
        opened,
        openProject,
    };
});
