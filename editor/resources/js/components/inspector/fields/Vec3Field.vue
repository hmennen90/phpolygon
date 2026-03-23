<script setup lang="ts">
import type { Vec3 } from '@/types';

const props = defineProps<{
    label: string;
    modelValue: Vec3;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: Vec3];
}>();

function updateX(e: Event) {
    const x = parseFloat((e.target as HTMLInputElement).value);
    if (!isNaN(x)) emit('update:modelValue', { ...props.modelValue, x });
}

function updateY(e: Event) {
    const y = parseFloat((e.target as HTMLInputElement).value);
    if (!isNaN(y)) emit('update:modelValue', { ...props.modelValue, y });
}

function updateZ(e: Event) {
    const z = parseFloat((e.target as HTMLInputElement).value);
    if (!isNaN(z)) emit('update:modelValue', { ...props.modelValue, z });
}
</script>

<template>
    <div class="flex items-center gap-1 px-1 py-0.5">
        <label class="text-xs text-editor-muted w-20 shrink-0 truncate">{{ label }}</label>
        <div class="flex-1 flex gap-1">
            <div class="flex items-center gap-0.5 flex-1">
                <span class="text-xs text-red-400">X</span>
                <input
                    type="number"
                    step="0.1"
                    :value="modelValue.x"
                    class="flex-1 bg-editor-input border border-editor-border text-editor-text text-sm px-1 py-0.5 rounded focus:border-editor-accent focus:outline-none w-0 min-w-0"
                    @input="updateX"
                />
            </div>
            <div class="flex items-center gap-0.5 flex-1">
                <span class="text-xs text-green-400">Y</span>
                <input
                    type="number"
                    step="0.1"
                    :value="modelValue.y"
                    class="flex-1 bg-editor-input border border-editor-border text-editor-text text-sm px-1 py-0.5 rounded focus:border-editor-accent focus:outline-none w-0 min-w-0"
                    @input="updateY"
                />
            </div>
            <div class="flex items-center gap-0.5 flex-1">
                <span class="text-xs text-blue-400">Z</span>
                <input
                    type="number"
                    step="0.1"
                    :value="modelValue.z"
                    class="flex-1 bg-editor-input border border-editor-border text-editor-text text-sm px-1 py-0.5 rounded focus:border-editor-accent focus:outline-none w-0 min-w-0"
                    @input="updateZ"
                />
            </div>
        </div>
    </div>
</template>
