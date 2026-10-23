import { API_BASE, USE_MOCKS } from './config.js';

const CLE_TOKEN = 'nrv.token';
const CLE_UTILISATEUR = 'nrv.utilisateur';

export class ApiError extends Error {
    constructor(code, message) {
        super(message);
        this.code = code;
    }
}

export async function getJSON(chemin) {
    return USE_MOCKS ? getMock(chemin) : appelerApi('GET', chemin);
}

export async function envoyerJSON(methode, chemin, corps) {
    return USE_MOCKS ? getMock(chemin) : appelerApi(methode, chemin, corps);
}

export function lireSession() {
    const token = sessionStorage.getItem(CLE_TOKEN);
    if (!token) return null;
    return { token, utilisateur: JSON.parse(sessionStorage.getItem(CLE_UTILISATEUR)) };
}

export function ecrireSession(token, utilisateur) {
    sessionStorage.setItem(CLE_TOKEN, token);
    sessionStorage.setItem(CLE_UTILISATEUR, JSON.stringify(utilisateur));
}

export function effacerSession() {
    sessionStorage.removeItem(CLE_TOKEN);
    sessionStorage.removeItem(CLE_UTILISATEUR);
}

async function appelerApi(methode, chemin, corps) {
    const headers = { Accept: 'application/json' };
    const session = lireSession();
    if (session) headers.Authorization = `Bearer ${session.token}`;
    if (corps !== undefined) headers['Content-Type'] = 'application/json';

    let response;
    try {
        response = await fetch(API_BASE + chemin, {
            method: methode,
            headers,
            body: corps === undefined ? undefined : JSON.stringify(corps),
        });
    } catch {
        throw new ApiError(0, "Impossible de joindre l'API");
    }

    if (response.status === 401 && !chemin.startsWith('/auth/')) {
        effacerSession();
        const retour = window.location.pathname.split('/').pop() + window.location.search;
        window.location.href = `connexion.html?retour=${encodeURIComponent(retour)}`;
    }

    if (response.status === 204) return null;

    const data = await response.json().catch(() => null);
    if (!response.ok) {
        throw new ApiError(response.status, data?.message ?? `Erreur ${response.status}`);
    }
    return data;
}

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
            return chargerMock(id ? `soiree-${id}.json` : 'soirees.json');
        case 'panier':
            return chargerMock('panier.json');
        case 'spectacles':
            return filtrerSpectaclesMock(await chargerMock('spectacles.json'), url.searchParams, chemin);
        default:
            throw new ApiError(404, `Route ${url.pathname} inconnue`);
    }
}

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
