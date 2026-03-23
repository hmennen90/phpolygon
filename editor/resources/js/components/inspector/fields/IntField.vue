<script setup lang="ts">
const props = defineProps<{
    label: string;
    modelValue: number;
    min?: number;
    max?: number;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number];
}>();

function onInput(e: Event) {
    const val = parseInt((e.target as HTMLInputElement).value, 10);
    if (!isNaN(val)) emit('update:modelValue', val);
}
</script>

<template>
    <div class="flex items-center gap-1 px-1 py-0.5">
        <label class="text-xs text-editor-muted w-20 shrink-0 truncate">{{ label }}</label>
        <input
            type="number"
            :value="modelValue"
            :min="min"
            :max="max"
            step="1"
            class="flex-1 bg-editor-input border border-editor-border text-editor-text text-sm px-1 py-0.5 rounded focus:border-editor-accent focus:outline-none w-0 min-w-0"
            @input="onInput"
        />
    </div>
</template>
