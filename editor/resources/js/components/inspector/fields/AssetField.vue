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

function browse() {
    // Placeholder: will integrate with NativePHP file dialog
    console.log('Asset browse not yet implemented');
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
        <button
            class="text-xs px-1.5 py-0.5 rounded border border-editor-border hover:bg-editor-hover active:bg-editor-active shrink-0"
            @click="browse"
        >
            Browse
        </button>
    </div>
</template>
