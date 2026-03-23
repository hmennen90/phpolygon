<script setup lang="ts">
defineProps<{
    label: string;
    modelValue: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

function toggle(e: Event) {
    emit('update:modelValue', (e.target as HTMLInputElement).checked);
}
</script>

<template>
    <div class="flex items-center gap-1 px-1 py-0.5">
        <label class="text-xs text-editor-muted w-20 shrink-0 truncate">{{ label }}</label>
        <input
            type="checkbox"
            :checked="modelValue"
            class="accent-editor-accent"
            @change="toggle"
        />
    </div>
</template>
