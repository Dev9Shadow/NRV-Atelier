import { envoyerJSON, ecrireSession, effacerSession, lireSession } from './api.js';

export async function connecter(email, password) {
    try {
        const data = await envoyerJSON('POST', '/auth/signin', { email, password });
        if (data && data.token) {
            ecrireSession(data.token, {
                nom: data.nom || email,
                role: data.role || 'USER'
            });
            return true;
        }
        return false;
    } catch (error) {
        console.error('Erreur de connexion:', error);
        throw error;
    }
}

export async function inscrire(nom, prenom, email, password) {
    try {
        const data = await envoyerJSON('POST', '/auth/signup', { nom, prenom, email, password });
        return data;
    } catch (error) {
        console.error('Erreur d\'inscription:', error);
        throw error;
    }
}

export function deconnecter() {
    effacerSession();
    window.location.href = 'index.html';
}

export function estAdmin() {
    const session = lireSession();
    return session && session.utilisateur && session.utilisateur.role === 'ADMIN';
}

export function utilisateurConnecte() {
    return lireSession() !== null;
}

export function getNomUtilisateur() {
    const session = lireSession();
    return session && session.utilisateur ? session.utilisateur.nom : null;
}