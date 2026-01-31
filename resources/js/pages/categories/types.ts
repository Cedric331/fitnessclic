export interface Category {
    id: number;
    name: string;
    type: 'private' | 'public';
    is_owner?: boolean;
    coach_name?: string | null;
}

export interface Filters {
    search?: string | null;
    show_private: boolean;
    show_public: boolean;
    team_id?: number | null;
}

export interface TeamOption {
    id: number;
    name: string;
}

