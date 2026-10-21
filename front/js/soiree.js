import { getJSON } from './api.js';
import { rendu } from './templates.js';

const fiche = document.querySelector('#fiche');
const id = new URLSearchParams(window.location.search).get('id') ?? '';

function afficherMessage(texte) {
    fiche.innerHTML = '';
    const message = document.createElement('p');
    message.className = 'message';
    message.textContent = texte;
    fiche.append(message);
}

function allerAuSpectacle() {
    const ancre = window.location.hash;
    if (!ancre) return;
    history.replaceState(null, '', window.location.pathname + window.location.search);
    window.location.hash = ancre;
}

async function afficherSoiree() {
    if (!/^\d+$/.test(id)) {
        afficherMessage('Soirée introuvable.');
        return;
    }

    try {
        const { soiree } = await getJSON(`/soirees/${id}`);
        fiche.innerHTML = rendu('tpl-soiree', soiree);
        document.title = `${soiree.nom} — NRV.net`;
        allerAuSpectacle();
    } catch (erreur) {
        afficherMessage(erreur.message);
    }
}

afficherSoiree();
