<script setup lang="ts">
const props = withDefaults(defineProps<{
    label: string;
    modelValue: number;
    min: number;
    max: number;
    step?: number;
}>(), {
    step: 0.01,
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
            type="range"
            :value="modelValue"
            :min="min"
            :max="max"
            :step="step"
            class="flex-1 h-4 accent-editor-accent"
            @input="onInput"
        />
        <span class="text-sm text-editor-text w-10 text-right tabular-nums">
            {{ typeof modelValue === 'number' ? modelValue.toFixed(2) : modelValue }}
        </span>
    </div>
</template>
