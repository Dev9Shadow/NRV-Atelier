import { apiRequest } from './api.js';

export async function connecter(email, password) {
    try {
        const data = await apiRequest('/login', 'POST', { email, password });
        if (data && data.token) {
            localStorage.setItem('jwt_token', data.token);
            localStorage.setItem('user_name', data.nom || email);
            localStorage.setItem('user_role', data.role || 'user');
            return true;
        }
        return false;
    } catch (error) {
        console.error('Erreur de connexion:', error);
        throw error;
    }
}

export async function inscrire(nom, email, password) {
    try {
        const data = await apiRequest('/register', 'POST', { nom, email, password });
        return data;
    } catch (error) {
        console.error('Erreur d\'inscription:', error);
        throw error;
    }
}

export function deconnecter() {
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('user_name');
    localStorage.removeItem('user_role');
    window.location.href = 'index.html';
}

export function estAdmin() {
    return localStorage.getItem('user_role') === 'admin';
}

export function utilisateurConnecte() {
    return localStorage.getItem('jwt_token') !== null;
}

export function getNomUtilisateur() {
    return localStorage.getItem('user_name');
}