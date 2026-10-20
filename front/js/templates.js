const cache = new Map();
const formatPrix = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });

Handlebars.registerHelper('dateLongue', (date) => {
    const texte = new Date(`${date}T00:00:00`).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
    return texte.charAt(0).toUpperCase() + texte.slice(1);
});

Handlebars.registerHelper('prix', (montant) => formatPrix.format(montant));

Handlebars.registerHelper('liste', (valeurs) => valeurs.join(', '));

Handlebars.registerHelper('idDepuisLien', (href) => href.split('/').pop());

export function rendu(idTemplate, donnees) {
    if (!cache.has(idTemplate)) {
        const source = document.getElementById(idTemplate).innerHTML;
        cache.set(idTemplate, Handlebars.compile(source));
    }
    return cache.get(idTemplate)(donnees);
}
