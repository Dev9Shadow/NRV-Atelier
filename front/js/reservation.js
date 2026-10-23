import { getJSON, envoyerJSON } from './api.js';
import { rendu } from './templates.js';

const REMISE_PASS = 0.03;
const formatPrix = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });

async function chargerAutresSoirees(idSoiree) {
    try {
        const { soirees } = await getJSON('/soirees');
        return soirees.map(({ soiree }) => soiree).filter((soiree) => soiree.id !== idSoiree);
    } catch {
        return [];
    }
}

export async function initialiserReservation(soiree) {
    const formulaire = document.querySelector('#reservation');
    if (!formulaire) return;

    const choixPass = formulaire.querySelector('#choix-pass');
    const blocPass = formulaire.querySelector('#reservation-pass');
    const selects = blocPass.querySelectorAll('select');
    const total = formulaire.querySelector('#reservation-total');
    const message = formulaire.querySelector('#reservation-message');

    const autres = await chargerAutresSoirees(soiree.id);
    if (autres.length < 2) {
        choixPass.disabled = true;
    } else {
        selects.forEach((select, index) => {
            select.innerHTML = rendu('tpl-options-soirees', { soirees: autres });
            select.selectedIndex = index;
        });
    }

    function lireChoix() {
        const donnees = new FormData(formulaire);
        const typeBillet = donnees.get('typeBillet');
        const soirees = typeBillet === 'PASS_3J'
            ? [soiree.id, Number(donnees.get('soiree2')), Number(donnees.get('soiree3'))]
            : [soiree.id];
        return { typeBillet, categorie: donnees.get('categorie'), soirees, quantite: Number(donnees.get('quantite')) };
    }

    function prixUnitaire({ typeBillet, categorie }) {
        const tarif = categorie === 'REDUIT' ? soiree.tarifs.reduit : soiree.tarifs.normal;
        return typeBillet === 'PASS_3J' ? Math.round(tarif * 3 * (1 - REMISE_PASS) * 100) / 100 : tarif;
    }

    function mettreAJour() {
        const estPass = choixPass.checked;
        blocPass.hidden = !estPass;
        blocPass.disabled = !estPass;
        const choix = lireChoix();
        total.value = formatPrix.format(prixUnitaire(choix) * (choix.quantite || 0));
        message.textContent = '';
    }

    formulaire.addEventListener('input', mettreAJour);
    formulaire.addEventListener('submit', async (evenement) => {
        evenement.preventDefault();
        const choix = lireChoix();

        if (new Set(choix.soirees).size !== choix.soirees.length) {
            message.textContent = 'Les 3 soirées du pass doivent être différentes.';
            return;
        }

        try {
            await envoyerJSON('POST', '/panier/lignes', choix);
            message.innerHTML = 'Billets ajoutés. <a href="panier.html">Voir le panier</a>';
            document.dispatchEvent(new CustomEvent('panier:modifie'));
        } catch (erreur) {
            message.textContent = erreur.message;
        }
    });

    mettreAJour();
}
