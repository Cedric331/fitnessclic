export interface Exercise {
    id: number;
    name: string;
    image_url: string;
    category_name: string;
    categories?: Array<{
        id: number;
        name: string;
    }>;
    created_at: string;
}

export interface ExercisesFilters {
    search?: string | null;
    category_id?: number | null;
    sort?: 'newest' | 'oldest';
    view?: 'grid-2' | 'grid-4' | 'grid-6' | 'grid-8';
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
    imported_public_exercise_ids?: number[];
}

