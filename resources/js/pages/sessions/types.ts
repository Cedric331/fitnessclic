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

export interface SessionExercise {
    exercise_id: number;
    exercise?: Exercise;
    sets?: number;
    repetitions?: string;
    rest_time?: string;
    duration?: string;
    description?: string;
    order: number;
}

export interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    email?: string;
    phone?: string;
    full_name: string;
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
    person_name?: string;
    session_date?: string;
    notes?: string;
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
    };
}

