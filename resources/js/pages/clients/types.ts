export interface TrainingSessionHistory {
    id: number;
    name?: string | null;
    notes?: string | null;
    session_date?: string | null;
    exercises_count: number;
    has_custom_layout?: boolean;
    coach_name?: string | null;
    is_owner?: boolean;
    created_at: string;
}

export interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string;
    is_active: boolean;
    internal_note?: string;
    coach_name?: string | null;
    is_owner?: boolean;
    training_sessions_count?: number;
    training_sessions?: TrainingSessionHistory[];
}

export interface CustomersData {
    data: Customer[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface CustomersFilters {
    search?: string;
    ownership?: 'all' | 'mine' | 'team';
}

export interface CustomersProps {
    customers: CustomersData;
    filters: CustomersFilters;
}

