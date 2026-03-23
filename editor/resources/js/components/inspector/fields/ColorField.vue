<script setup lang="ts">
import { computed } from 'vue';
import type { Color } from '@/types';

const props = defineProps<{
    label: string;
    modelValue: Color;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: Color];
}>();

function floatToHex(f: number): string {
    return Math.round(Math.max(0, Math.min(1, f)) * 255)
        .toString(16)
        .padStart(2, '0');
}

function hexToFloat(hex: string, offset: number): number {
    return parseInt(hex.slice(offset, offset + 2), 16) / 255;
}

const hexValue = computed(() => {
    const r = floatToHex(props.modelValue.r);
    const g = floatToHex(props.modelValue.g);
    const b = floatToHex(props.modelValue.b);
    return `#${r}${g}${b}`;
});

function onColorInput(e: Event) {
    const hex = (e.target as HTMLInputElement).value.replace('#', '');
    emit('update:modelValue', {
        r: hexToFloat(hex, 0),
        g: hexToFloat(hex, 2),
        b: hexToFloat(hex, 4),
        a: props.modelValue.a,
    });
}

function onAlphaInput(e: Event) {
    const a = parseFloat((e.target as HTMLInputElement).value);
    if (!isNaN(a)) {
        emit('update:modelValue', { ...props.modelValue, a: Math.max(0, Math.min(1, a)) });
    }
}
</script>

<template>
    <div class="flex items-center gap-1 px-1 py-0.5">
        <label class="text-xs text-editor-muted w-20 shrink-0 truncate">{{ label }}</label>
        <input
            type="color"
            :value="hexValue"
            class="w-6 h-6 border border-editor-border rounded cursor-pointer p-0 bg-transparent"
            @input="onColorInput"
        />
        <span class="text-xs text-editor-muted">{{ hexValue }}</span>
        <input
            type="number"
            :value="modelValue.a"
            min="0"
            max="1"
            step="0.05"
            class="w-12 bg-editor-input border border-editor-border text-editor-text text-sm px-1 py-0.5 rounded focus:border-editor-accent focus:outline-none"
            title="Alpha"
            @input="onAlphaInput"
        />
    </div>
</template>
