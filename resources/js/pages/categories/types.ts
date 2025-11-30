export interface Category {
    id: number;
    name: string;
    type: 'private' | 'public';
}

export interface Filters {
    search?: string | null;
    show_private: boolean;
    show_public: boolean;
}

