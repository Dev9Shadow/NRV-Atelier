import { getJSON } from './api.js';
import { rendu } from './templates.js';

const liste = document.querySelector('#spectacles');
const compteur = document.querySelector('#compteur');

async function afficherSpectacles(chemin = '/spectacles') {
    try {
        const data = await getJSON(chemin);
        liste.innerHTML = rendu('tpl-spectacles', data);
        compteur.textContent = `${data.count} spectacle${data.count > 1 ? 's' : ''}`;
    } catch (erreur) {
        liste.innerHTML = '';
        compteur.textContent = erreur.message;
    }
}

afficherSpectacles();
