import { getJSON } from './api.js';
import { rendu } from './templates.js';

Handlebars.registerHelper('typeLibelle', (type) => (type === 'PASS_3J' ? 'Pass 3 jours' : 'Billet 1 soirée'));
Handlebars.registerHelper('categorieLibelle', (categorie) => (categorie === 'REDUIT' ? 'Tarif réduit' : 'Tarif normal'));

const liste = document.querySelector('#billets');
const boutonImprimer = document.querySelector('#imprimer');
const tokens = new URLSearchParams(window.location.search)
    .getAll('token')
    .filter((token) => /^[0-9a-f]{64}$/.test(token));

function afficherMessage(texte) {
    liste.innerHTML = rendu('tpl-message', { texte });
}

async function afficherBillets() {
    if (tokens.length === 0) {
        afficherMessage('Billet introuvable.');
        return;
    }

    try {
        const reponses = await Promise.all(tokens.map((token) => getJSON(`/billets/${token}`)));
        liste.innerHTML = reponses.map(({ billet }) => rendu('tpl-billet', billet)).join('');
        boutonImprimer.hidden = false;
        if (reponses.length === 1) {
            document.title = `Billet ${reponses[0].billet.reference} — NRV.net`;
        }
    } catch (erreur) {
        afficherMessage(erreur.code === 404 ? 'Billet introuvable.' : erreur.message);
    }
}

boutonImprimer.addEventListener('click', () => window.print());

afficherBillets();
