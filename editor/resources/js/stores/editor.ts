import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useEditorStore = defineStore('editor', () => {
    const playing = ref(false);
    const theme = ref<'dark'>('dark');

    function play() {
        playing.value = true;
    }

    function stop() {
        playing.value = false;
    }

    return {
        playing,
        theme,
        play,
        stop,
    };
});
