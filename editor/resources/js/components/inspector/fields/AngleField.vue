<script setup lang="ts">
defineProps<{
    label: string;
    modelValue: number;
}>();

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
        <div class="flex-1 flex items-center gap-0.5">
            <input
                type="number"
                step="0.1"
                :value="modelValue"
                class="flex-1 bg-editor-input border border-editor-border text-editor-text text-sm px-1 py-0.5 rounded focus:border-editor-accent focus:outline-none w-0 min-w-0"
                @input="onInput"
            />
            <span class="text-xs text-editor-muted">&deg;</span>
        </div>
    </div>
</template>
