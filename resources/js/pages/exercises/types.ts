export interface Exercise {
    id: number;
    name: string;
    image_url: string;
    category_name: string;
    created_at: string;
}

export interface ExercisesFilters {
    search?: string | null;
    category_id?: number | null;
    sort?: 'newest' | 'oldest';
    view?: 'grid-1' | 'grid-2' | 'grid-4' | 'list';
}

export interface ExercisesProps {
    exercises: {
        data: Exercise[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        has_more: boolean;
    };
    filters: ExercisesFilters;
    categories: Array<{
        id: number;
        name: string;
    }>;
}

