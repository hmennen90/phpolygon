import { post } from './api';
import type {
    ComponentListResponse,
    SchemaResponse,
    SceneData,
    HierarchyResponse,
    ComponentSchemaDTO,
    EntityNode,
} from '@/types';

function cmd<T = unknown>(command: string, args: Record<string, unknown> = {}): Promise<T> {
    return post<T>('/command', { command, args });
}

export function listComponents(grouped = true): Promise<ComponentListResponse> {
    return cmd<ComponentListResponse>('listComponents', { grouped });
}

export function getComponentSchema(className: string): Promise<SchemaResponse> {
    return cmd<SchemaResponse>('getComponentSchema', { className });
}

export function loadScene(sceneName: string): Promise<SceneData> {
    return cmd<SceneData>('loadScene', { sceneName });
}

export function saveScene(): Promise<{ saved: boolean }> {
    return cmd<{ saved: boolean }>('saveScene');
}

export function createEntity(
    name: string,
    parent: string | null = null,
): Promise<EntityNode> {
    return cmd<EntityNode>('createEntity', { name, parent });
}

export function deleteEntity(name: string): Promise<{ deleted: boolean }> {
    return cmd<{ deleted: boolean }>('deleteEntity', { name });
}

export function addComponent(
    entity: string,
    component: string,
): Promise<{ added: boolean }> {
    return cmd<{ added: boolean }>('addComponent', { entity, component });
}

export function removeComponent(
    entity: string,
    component: string,
): Promise<{ removed: boolean }> {
    return cmd<{ removed: boolean }>('removeComponent', { entity, component });
}

export function updateProperty(
    entity: string,
    component: string,
    property: string,
    value: unknown,
): Promise<{ updated: boolean }> {
    return cmd<{ updated: boolean }>('updateProperty', {
        entity,
        component,
        property,
        value,
    });
}

export function getEntityHierarchy(): Promise<HierarchyResponse> {
    return cmd<HierarchyResponse>('getEntityHierarchy');
}

export function undo(): Promise<{ undone: boolean }> {
    return cmd<{ undone: boolean }>('undo');
}

export function redo(): Promise<{ redone: boolean }> {
    return cmd<{ redone: boolean }>('redo');
}

export function renameEntity(
    oldName: string,
    newName: string,
): Promise<{ renamed: boolean }> {
    return cmd<{ renamed: boolean }>('renameEntity', { oldName, newName });
}
