<script setup lang="ts">
const props = withDefaults(defineProps<{
    label: string;
    modelValue: number;
    min?: number;
    max?: number;
    step?: number;
}>(), {
    step: 0.1,
});

const emit = defineEmits<{
    'update:modelValue': [value: number];
}>();

function onInput(e: Event) {
    const val = parseFloat((e.target as HTMLInputElement).value);
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
            :step="step"
            class="flex-1 bg-editor-input border border-editor-border text-editor-text text-sm px-1 py-0.5 rounded focus:border-editor-accent focus:outline-none w-0 min-w-0"
            @input="onInput"
        />
    </div>
</template>
