// Accès aux données : un seul point d'entrée, getJSON(chemin),
// qui interroge soit l'API, soit les mocks selon config.js.
// Les chemins sont ceux de l'API (et des liens HATEOAS) : '/spectacles?date=…', '/soirees/1', '/styles'.

import { API_BASE, USE_MOCKS } from './config.js';

/** Erreur renvoyée par l'API au format { type: "error", code, message } */
export class ApiError extends Error {
    constructor(code, message) {
        super(message);
        this.code = code;
    }
}

export async function getJSON(chemin) {
    return USE_MOCKS ? getMock(chemin) : getApi(chemin);
}

async function getApi(chemin) {
    let response;
    try {
        response = await fetch(API_BASE + chemin, { headers: { Accept: 'application/json' } });
    } catch {
        throw new ApiError(0, "Impossible de joindre l'API");
    }

    const data = await response.json().catch(() => null);
    if (!response.ok) {
        throw new ApiError(response.status, data?.message ?? `Erreur ${response.status}`);
    }
    return data;
}

// ---------- Mocks : imitent les routes de l'API à partir des fichiers JSON ----------

async function chargerMock(fichier) {
    const response = await fetch(`mocks/${fichier}`);
    if (!response.ok) {
        throw new ApiError(404, `Mock ${fichier} introuvable`);
    }
    return response.json();
}

async function getMock(chemin) {
    const url = new URL(chemin, window.location.origin);
    const [ressource, id] = url.pathname.split('/').filter(Boolean);

    switch (ressource) {
        case 'styles':
            return chargerMock('styles.json');
        case 'soirees':
            return chargerMock(`soiree-${id}.json`);
        case 'spectacles':
            return filtrerSpectaclesMock(await chargerMock('spectacles.json'), url.searchParams, chemin);
        default:
            throw new ApiError(404, `Route ${url.pathname} inconnue`);
    }
}

// Reproduit côté client les filtres ?date=, ?style= (id) et ?soiree= (id) de l'API
async function filtrerSpectaclesMock(collection, params, chemin) {
    const date = params.get('date');
    const styleId = params.get('style');
    const soireeId = params.get('soiree');

    let libelleStyle = null;
    if (styleId) {
        const { styles } = await chargerMock('styles.json');
        libelleStyle = styles.find((s) => String(s.id) === styleId)?.libelle ?? null;
    }

    const spectacles = collection.spectacles.filter(({ spectacle, links }) =>
        (!date || spectacle.date === date)
        && (!styleId || spectacle.style === libelleStyle)
        && (!soireeId || links.soiree.href === `/soirees/${soireeId}`)
    );

    return { ...collection, count: spectacles.length, spectacles, links: { self: { href: chemin } } };
}
