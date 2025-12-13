export interface Exercise {
    id: number;
    title: string;
    description?: string;
    image_url?: string;
    suggested_duration?: string;
    user_id?: number;
    categories?: Array<{
        id: number;
        name: string;
    }>;
}

export interface ExerciseSet {
    id?: number;
    set_number: number;
    repetitions?: number | null;
    weight?: number | null;
    rest_time?: string | null;
    duration?: string | null;
    use_duration?: boolean; // true = utiliser durée, false = utiliser répétitions (par set)
    use_bodyweight?: boolean; // true = poids de corps, false = charge (weight) (par set)
    order: number;
}

export interface SessionExercise {
    id?: number;
    exercise_id: number;
    exercise?: Exercise;
    custom_exercise_name?: string | null; // Nom personnalisé de l'exercice (spécifique à la séance)
    sets?: ExerciseSet[]; // Séries multiples
    // Champs pour compatibilité avec l'ancien système (si pas de séries multiples)
    repetitions?: number | null;
    weight?: number | null;
    rest_time?: string | null;
    duration?: string | null;
    use_duration?: boolean; // true = utiliser durée, false = utiliser répétitions
    use_bodyweight?: boolean; // true = poids de corps, false = charge (weight)
    description?: string | null; // Notes/commentaires
    sets_count?: number | null; // Nombre de séries
    order: number;
    
    // Nouveaux champs pour Super 7
    block_id?: number | null;
    block_type?: 'standard' | 'set' | null;
    position_in_block?: number | null;
}

// Nouveau type pour les blocs (géré en mémoire côté frontend)
export interface SessionBlock {
    id: number; // Généré automatiquement
    type: 'standard' | 'set';
    exercises: SessionExercise[];
    order: number;
    block_description?: string | null; // Consignes pour l'ensemble du bloc Super 7
}

export interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    email?: string;
    phone?: string;
    full_name: string;
    is_active?: boolean;
}

export interface Category {
    id: number;
    name: string;
}

export interface Session {
    id: number;
    name?: string;
    customer_id?: number;
    customer?: Customer;
    customers?: Customer[];
    person_name?: string;
    session_date?: string;
    notes?: string;
    exercises_count?: number;
    has_custom_layout?: boolean;
    layout?: {
        id?: number;
        layout_data?: any[];
        canvas_width?: number;
        canvas_height?: number;
    } | null;
    exercises?: Array<{
        id: number;
        title: string;
        image_url?: string;
        pivot: {
            repetitions?: string;
            rest_time?: string;
            duration?: string;
            additional_description?: string;
            order: number;
        };
    }>;
    sessionExercises?: Array<{
        id: number;
        exercise_id: number;
        exercise?: Exercise;
        repetitions?: number | null;
        weight?: number | null;
        rest_time?: string | null;
        duration?: string | null;
        additional_description?: string | null;
        sets_count?: number | null;
        order: number;
        block_id?: number | null;
        block_type?: 'standard' | 'set' | null;
        position_in_block?: number | null;
        sets?: ExerciseSet[];
    }>;
    created_at: string;
    updated_at: string;
}

export interface SessionFilters {
    search?: string | null;
    category_id?: number | null;
}

export interface CreateSessionProps {
    exercises: Exercise[];
    categories: Category[];
    customers: Customer[];
    filters: SessionFilters;
}

export interface EditSessionProps extends CreateSessionProps {
    session: Session;
}

export interface SessionsProps {
    sessions: {
        data: Session[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    customers: Customer[];
    filters: {
        search?: string | null;
        customer_id?: number | null;
        sort?: 'newest' | 'oldest';
    };
}

