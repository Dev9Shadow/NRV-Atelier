import { getJSON, lireSession, effacerSession } from './api.js';

const liste = document.querySelector('.site-header__nav ul');
const pageCourante = window.location.pathname.split('/').pop() || 'index.html';

function ajouterLien(href, texte) {
    const item = document.createElement('li');
    const lien = document.createElement('a');
    lien.href = href;
    lien.textContent = texte;
    if (href === pageCourante) lien.setAttribute('aria-current', 'page');
    item.append(lien);
    liste.append(item);
    return lien;
}

function ajouterDeconnexion() {
    const item = document.createElement('li');
    const bouton = document.createElement('button');
    bouton.type = 'button';
    bouton.className = 'site-header__deconnexion';
    bouton.textContent = 'Déconnexion';
    bouton.addEventListener('click', () => {
        effacerSession();
        window.location.href = 'index.html';
    });
    item.append(bouton);
    liste.append(item);
}

async function afficherNombreDeBillets(lienPanier) {
    try {
        const { panier } = await getJSON('/panier', { rediriger: false });
        const nombre = panier.lignes.reduce((total, ligne) => total + ligne.quantite, 0);
        lienPanier.textContent = nombre > 0 ? `Panier (${nombre})` : 'Panier';
    } catch {
        if (lireSession()) {
            lienPanier.textContent = 'Panier';
        } else {
            afficherNavigation();
        }
    }
}

function afficherNavigation() {
    const session = lireSession();
    liste.replaceChildren();

    ajouterLien('index.html', 'Programme');
    const lienPanier = ajouterLien('panier.html', 'Panier');

    if (session?.utilisateur?.role === 'ADMIN') {
        ajouterLien('admin.html', 'Admin');
    }

    if (session) {
        ajouterDeconnexion();
        afficherNombreDeBillets(lienPanier);
        document.addEventListener('panier:modifie', () => afficherNombreDeBillets(lienPanier));
    } else {
        const retour = pageCourante === 'connexion.html' ? '' : `?retour=${encodeURIComponent(pageCourante + window.location.search)}`;
        ajouterLien(`connexion.html${retour}`, 'Connexion');
    }
}

afficherNavigation();
