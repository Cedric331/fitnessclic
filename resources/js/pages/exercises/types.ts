export interface Exercise {
    id: number;
    name: string;
    image_url: string;
    user_id?: number;
    category_name: string;
    categories?: Array<{
        id: number;
        name: string;
    }>;
    is_premium?: boolean;
    created_at: string;
}

export interface ExercisesFilters {
    search?: string | null;
    category_id?: number | null;
    sort?: 'newest' | 'oldest' | 'alphabetical' | 'alphabetical-desc';
    view?: 'grid-2' | 'grid-4' | 'grid-6' | 'grid-8';
    is_premium?: boolean | null;
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

