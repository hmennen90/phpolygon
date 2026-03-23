// ─── Entity & Scene ─────────────────────────────────────────────────────────

export interface EntityNode {
    name: string;
    components: ComponentData[];
    children: EntityNode[];
    expanded?: boolean;
}

export interface ComponentData {
    _class: string;
    properties: Record<string, unknown>;
}

export interface SceneData {
    name: string;
    entities: EntityNode[];
    dirty: boolean;
}

// ─── Component Schema ───────────────────────────────────────────────────────

export interface PropertySchemaDTO {
    name: string;
    type: string;
    default: unknown;
    editorHint?: string;
    min?: number;
    max?: number;
    step?: number;
    options?: string[];
    nullable?: boolean;
}

export interface ComponentSchemaDTO {
    className: string;
    shortName: string;
    category: string;
    description?: string;
    properties: PropertySchemaDTO[];
}

// ─── API Responses ──────────────────────────────────────────────────────────

export interface ApiResponse<T = unknown> {
    success: boolean;
    data: T;
    error?: string;
}

export interface CommandResponse<T = unknown> {
    success: boolean;
    data: T;
    error?: string;
}

export interface HierarchyResponse {
    entities: EntityNode[];
}

export interface ComponentListResponse {
    categories: Record<string, ComponentSchemaDTO[]>;
}

export interface SchemaResponse {
    schema: ComponentSchemaDTO;
}

// ─── Project ────────────────────────────────────────────────────────────────

export interface ProjectConfig {
    name: string;
    projectDir: string;
    scenesPath: string;
    assetsPath: string;
    entryScene: string;
    scenes: string[];
}

// ─── Vec / Color ────────────────────────────────────────────────────────────

export interface Vec2 {
    x: number;
    y: number;
}

export interface Vec3 {
    x: number;
    y: number;
    z: number;
}

export interface Color {
    r: number;
    g: number;
    b: number;
    a: number;
}
