export type Coach = {
    slug: string;
    name: string | null;
    headline: string | null;
    hourly_rate: number | null;
    city: string | null;
    distance_km: number | null;
    specialties: string[];
    avatar_url: string | null;
    is_founder: boolean;
};
