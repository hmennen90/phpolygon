<script setup lang="ts">
defineProps<{
    label: string;
    modelValue: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function onInput(e: Event) {
    emit('update:modelValue', (e.target as HTMLInputElement).value);
}
</script>

<template>
    <div class="flex items-center gap-1 px-1 py-0.5">
        <label class="text-xs text-editor-muted w-20 shrink-0 truncate">{{ label }}</label>
        <input
            type="text"
            :value="modelValue"
            class="flex-1 bg-editor-input border border-editor-border text-editor-text text-sm px-1 py-0.5 rounded focus:border-editor-accent focus:outline-none w-0 min-w-0"
            @input="onInput"
        />
    </div>
</template>
