export type PermissionLevel = 'none' | 'read' | 'write';

export type Section =
    | 'overview'
    | 'budget'
    | 'guests'
    | 'seating'
    | 'vendors'
    | 'timeline'
    | 'checklist'
    | 'inspiration'
    | 'gallery'
    | 'website'
    | 'crew'
    | 'collaborators'
    | 'settings';

export type ActiveWedding = {
    id: number;
    name: string;
    slug: string;
    event_date: string | null;
};

export type WeddingListItem = {
    id: number;
    name: string;
    slug: string;
};

export type WeddingShared = {
    active: ActiveWedding | null;
    list: WeddingListItem[];
    permissions: Partial<Record<Section, PermissionLevel>>;
};
