import { getJSON } from './api.js';
import { rendu } from './templates.js';

const formulaire = document.querySelector('#filtres');
const selectDate = document.querySelector('#filtre-date');
const selectStyle = document.querySelector('#filtre-style');
const liste = document.querySelector('#spectacles');
const compteur = document.querySelector('#compteur');

function afficherSpectacles(data) {
    liste.innerHTML = rendu('tpl-spectacles', data);
    compteur.textContent = `${data.count} spectacle${data.count > 1 ? 's' : ''}`;
}

function afficherErreur(erreur) {
    liste.innerHTML = '';
    compteur.textContent = erreur.message;
}

async function filtrer() {
    const params = new URLSearchParams();
    if (selectDate.value) params.set('date', selectDate.value);
    if (selectStyle.value) params.set('style', selectStyle.value);
    const requete = params.toString();

    try {
        afficherSpectacles(await getJSON(`/spectacles${requete ? `?${requete}` : ''}`));
    } catch (erreur) {
        afficherErreur(erreur);
    }
}

async function initialiser() {
    try {
        const [tous, { styles }] = await Promise.all([getJSON('/spectacles'), getJSON('/styles')]);
        const dates = [...new Set(tous.spectacles.map(({ spectacle }) => spectacle.date))].sort();

        selectDate.insertAdjacentHTML('beforeend', rendu('tpl-dates', { dates }));
        selectStyle.insertAdjacentHTML('beforeend', rendu('tpl-styles', { styles }));
        afficherSpectacles(tous);
    } catch (erreur) {
        afficherErreur(erreur);
    }
}

formulaire.addEventListener('change', filtrer);
formulaire.addEventListener('reset', () => setTimeout(filtrer));
formulaire.addEventListener('submit', (evenement) => evenement.preventDefault());

initialiser();
