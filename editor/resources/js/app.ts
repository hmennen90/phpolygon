import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHashHistory } from 'vue-router';
import EditorLayout from '@/components/layout/EditorLayout.vue';

const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        { path: '/', component: EditorLayout },
    ],
});

const app = createApp({
    template: '<router-view />',
});

app.use(createPinia());
app.use(router);
app.mount('#app');
