/**
 * Recherche de communes françaises via l'API Géo (geo.api.gouv.fr).
 * Service public et gratuit du gouvernement français, sans clé d'API.
 * @see https://geo.api.gouv.fr/decoupage-administratif/communes
 */

const BASE_URL = 'https://geo.api.gouv.fr/communes';

export type Commune = {
    city: string;
    postalCode: string | null;
    lat: number | null;
    lng: number | null;
    department: string | null;
};

type RawCommune = {
    nom: string;
    codesPostaux?: string[];
    centre?: { coordinates?: [number, number] };
    departement?: { nom?: string };
};

const mapCommune = (raw: RawCommune, preferredPostalCode?: string): Commune => {
    const postalCodes = raw.codesPostaux ?? [];
    const postalCode =
        preferredPostalCode && postalCodes.includes(preferredPostalCode)
            ? preferredPostalCode
            : (postalCodes[0] ?? null);

    // centre = GeoJSON Point : coordinates = [longitude, latitude]
    const coords = raw.centre?.coordinates;

    return {
        city: raw.nom,
        postalCode,
        lat: coords ? coords[1] : null,
        lng: coords ? coords[0] : null,
        department: raw.departement?.nom ?? null,
    };
};

/**
 * Recherche des communes correspondant à la saisie (nom ou code postal).
 */
export async function searchCommunes(query: string, signal?: AbortSignal): Promise<Commune[]> {
    const term = query.trim();

    if (term.length < 2) {
        return [];
    }

    const params = new URLSearchParams({
        fields: 'nom,codesPostaux,centre,departement',
        boost: 'population',
        limit: '7',
    });

    const isPostalCode = /^\d{5}$/.test(term);
    if (isPostalCode) {
        params.set('codePostal', term);
    } else {
        params.set('nom', term);
    }

    const response = await fetch(`${BASE_URL}?${params.toString()}`, { signal });

    if (!response.ok) {
        throw new Error(`Géo API: ${response.status}`);
    }

    const data = (await response.json()) as RawCommune[];

    if (!Array.isArray(data)) {
        return [];
    }

    return data.map((raw) => mapCommune(raw, isPostalCode ? term : undefined));
}
