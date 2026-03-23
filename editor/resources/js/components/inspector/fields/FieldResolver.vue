<script setup lang="ts">
import { computed } from 'vue';
import type { PropertySchemaDTO } from '@/types';
import { useSceneStore } from '@/stores/scene';
import FloatField from './FloatField.vue';
import IntField from './IntField.vue';
import BoolField from './BoolField.vue';
import StringField from './StringField.vue';
import Vec2Field from './Vec2Field.vue';
import Vec3Field from './Vec3Field.vue';
import ColorField from './ColorField.vue';
import SliderField from './SliderField.vue';
import AngleField from './AngleField.vue';
import AssetField from './AssetField.vue';

const props = defineProps<{
    schema: PropertySchemaDTO;
    value: unknown;
    entityName: string;
    componentClass: string;
}>();

const sceneStore = useSceneStore();

const fieldComponent = computed(() => {
    const hint = props.schema.editorHint;
    const type = props.schema.type;

    // Editor hint takes priority
    if (hint) {
        switch (hint) {
            case 'slider': return SliderField;
            case 'angle': return AngleField;
            case 'color': return ColorField;
            case 'asset': return AssetField;
            case 'vec2': return Vec2Field;
            case 'vec3': return Vec3Field;
        }
    }

    // Fall back to type
    switch (type) {
        case 'float':
        case 'double': return FloatField;
        case 'int':
        case 'integer': return IntField;
        case 'bool':
        case 'boolean': return BoolField;
        case 'string': return StringField;
        case 'Vec2': return Vec2Field;
        case 'Vec3': return Vec3Field;
        case 'Color': return ColorField;
        default: return StringField;
    }
});

const fieldProps = computed(() => {
    const base: Record<string, unknown> = {
        label: props.schema.name,
        modelValue: props.value ?? props.schema.default,
    };

    if (props.schema.min !== undefined) base.min = props.schema.min;
    if (props.schema.max !== undefined) base.max = props.schema.max;
    if (props.schema.step !== undefined) base.step = props.schema.step;

    return base;
});

async function onUpdate(newValue: unknown) {
    await sceneStore.updateProperty(
        props.entityName,
        props.componentClass,
        props.schema.name,
        newValue,
    );
}
</script>

<template>
    <component
        :is="fieldComponent"
        v-bind="fieldProps"
        @update:model-value="onUpdate"
    />
</template>
