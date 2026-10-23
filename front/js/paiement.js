import { envoyerJSON, getJSON, lireSession } from './api.js';
import { rendu } from './templates.js';

Handlebars.registerHelper('typeLibelle', (type) => (type === 'PASS_3J' ? 'Pass 3 jours' : 'Billet 1 soirée'));
Handlebars.registerHelper('categorieLibelle', (categorie) => (categorie === 'REDUIT' ? 'Tarif réduit' : 'Tarif normal'));

const contenu = document.querySelector('#contenu');
const idCommande = new URLSearchParams(window.location.search).get('commande') ?? '';

function afficherMessage(texte, lien = null) {
    contenu.innerHTML = rendu('tpl-message', { texte, lien });
}

function formaterNumero(champ) {
    const chiffres = champ.value.replace(/\D/g, '').slice(0, 16);
    champ.value = chiffres.replace(/(\d{4})(?=\d)/g, '$1 ');
}

function formaterExpiration(champ, evenement) {
    const chiffres = champ.value.replace(/\D/g, '').slice(0, 4);
    const effacement = evenement.inputType?.startsWith('delete');
    champ.value = chiffres.length > 2 || (chiffres.length === 2 && !effacement)
        ? `${chiffres.slice(0, 2)}/${chiffres.slice(2)}`
        : chiffres;
}

function verifierChamps(formulaire) {
    let valide = true;
    for (const nom of ['numero', 'expiration', 'cvc']) {
        const champ = formulaire.elements[nom];
        const ok = champ.checkValidity();
        champ.setAttribute('aria-invalid', String(!ok));
        formulaire.querySelector(`#erreur-${nom}`).classList.toggle('visible', !ok);
        valide &&= ok;
    }
    return valide;
}

function afficherErreurPaiement(zone, erreur) {
    zone.innerHTML = '';
    const message = document.createElement('p');
    message.textContent = erreur.code === 409 && erreur.message.startsWith('Plus assez de places')
        ? `${erreur.message}. Modifiez votre panier pour continuer.`
        : erreur.message;
    zone.append(message);

    if (erreur.code === 409) {
        const lien = document.createElement('a');
        lien.href = 'panier.html';
        lien.textContent = 'Retour au panier';
        zone.append(lien);
    }
    zone.classList.add('visible');
}

function afficherConfirmation(commande) {
    const billets = commande.billets.map(({ billet, links }) => ({ billet, token: links.self.href.split('/').pop() }));
    const lienTous = `billet.html?${billets.map(({ token }) => `token=${token}`).join('&')}`;
    contenu.innerHTML = rendu('tpl-confirmation', { ...commande, billets, lienTous });
    document.title = 'Paiement accepté — NRV.net';
}

function brancherFormulaire(commande) {
    const formulaire = document.querySelector('#form-paiement');
    const bouton = formulaire.querySelector('button[type="submit"]');
    const zoneErreur = formulaire.querySelector('#erreur-paiement');
    const libelleBouton = bouton.textContent;

    formulaire.elements.numero.addEventListener('input', (e) => formaterNumero(e.target));
    formulaire.elements.expiration.addEventListener('input', (e) => formaterExpiration(e.target, e));

    formulaire.addEventListener('submit', async (evenement) => {
        evenement.preventDefault();
        zoneErreur.classList.remove('visible');
        if (!verifierChamps(formulaire)) return;

        bouton.disabled = true;
        bouton.textContent = 'Paiement en cours…';
        try {
            const { commande: payee } = await envoyerJSON('POST', `/commandes/${commande.id}/paiement`, {
                numero: formulaire.elements.numero.value,
                expiration: formulaire.elements.expiration.value,
                cvc: formulaire.elements.cvc.value,
            });
            afficherConfirmation(payee);
        } catch (erreur) {
            afficherErreurPaiement(zoneErreur, erreur);
            bouton.disabled = false;
            bouton.textContent = libelleBouton;
        }
    });
}

async function initialiser() {
    if (!/^\d+$/.test(idCommande)) {
        afficherMessage('Aucune commande à payer.', { href: 'index.html', texte: 'Retour au programme' });
        return;
    }
    if (!lireSession()) {
        window.location.href = `connexion.html?retour=${encodeURIComponent(`paiement.html?commande=${idCommande}`)}`;
        return;
    }

    try {
        const { commande } = await getJSON(`/commandes/${idCommande}`);
        if (commande.statut === 'PAYEE') {
            afficherConfirmation(commande);
        } else if (commande.statut !== 'VALIDEE') {
            afficherMessage('Validez d\'abord votre panier.', { href: 'panier.html', texte: 'Voir mon panier' });
        } else {
            contenu.innerHTML = rendu('tpl-paiement', commande);
            brancherFormulaire(commande);
        }
    } catch (erreur) {
        afficherMessage(erreur.message, { href: 'index.html', texte: 'Retour au programme' });
    }
}

initialiser();
