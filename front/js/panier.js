import { getJSON, envoyerJSON } from './api.js';
import { rendu } from './templates.js';

const contenu = document.querySelector('#contenu');
const erreur = document.querySelector('#erreur');

function preparer(panier) {
    return {
        ...panier,
        lignes: panier.lignes.map((ligne) => ({
            ...ligne,
            libelle: ligne.typeBillet === 'PASS_3J' ? 'Pass 3 jours' : 'Billet soirée',
            tarif: ligne.categorie === 'REDUIT' ? 'réduit' : 'normal',
        })),
    };
}

async function afficherPanier() {
    try {
        const { panier } = await getJSON('/panier');
        contenu.innerHTML = rendu('tpl-panier', preparer(panier));
    } catch (e) {
        erreur.textContent = e.message;
    }
}

async function modifier(methode, chemin, corps) {
    erreur.textContent = '';
    try {
        await envoyerJSON(methode, chemin, corps);
    } catch (e) {
        erreur.textContent = e.message;
    }
    await afficherPanier();
}

async function valider() {
    erreur.textContent = '';
    try {
        const reponse = await envoyerJSON('POST', '/panier/validation');
        const { id } = reponse.commande ?? reponse.panier;
        window.location.href = `paiement.html?commande=${id}`;
    } catch (e) {
        erreur.textContent = e.message;
    }
}

contenu.addEventListener('change', (evenement) => {
    const idLigne = evenement.target.dataset.ligne;
    if (!idLigne) return;
    modifier('PATCH', `/panier/lignes/${idLigne}`, { quantite: Number(evenement.target.value) });
});

contenu.addEventListener('click', (evenement) => {
    const idSupprimer = evenement.target.dataset.supprimer;
    if (idSupprimer) {
        modifier('DELETE', `/panier/lignes/${idSupprimer}`);
    } else if (evenement.target.id === 'valider') {
        valider();
    }
});

afficherPanier();
