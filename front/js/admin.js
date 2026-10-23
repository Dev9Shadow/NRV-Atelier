import { estAdmin } from './auth.js';
import { getJSON } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
    const mainContent = document.getElementById('admin-content');

    // Vérification du rôle admin
    if (!estAdmin()) {
        mainContent.innerHTML = `
            <div class="access-denied">
                <h2>Accès refusé</h2>
                <p>Vous n'avez pas les droits d'administration nécessaires pour consulter cette page.</p>
                <a href="index.html">Retourner au programme</a>
            </div>
        `;
        return;
    }

    try {
        // Appel de l'API /admin/soirees
        const data = await getJSON('/admin/soirees');
        renderJauges(data);
    } catch (error) {
        console.error('Erreur lors du chargement des jauges:', error);
        mainContent.innerHTML = `<p style="color: red; text-align: center;">Erreur lors du chargement des données d'administration.</p>`;
    }
});

function renderJauges(jauges) {
    const mainContent = document.getElementById('admin-content');

    let totalPlacesVendues = 0;
    let totalCapacite = 0;
    let totalRecette = 0;

    jauges.forEach(j => {
        totalPlacesVendues += j.places_vendues || 0;
        totalCapacite += j.capacite || 0;
        totalRecette += j.recette || 0;
    });

    let html = `
        <h1>Suivi des Jauges et Recettes</h1>
        <div class="total-card">
            Total Général : ${totalPlacesVendues} / ${totalCapacite} places vendues | Recette totale : ${totalRecette.toFixed(2)} €
        </div>
        <table class="jauge-table">
            <thead>
                <tr>
                    <th>Soirée</th>
                    <th>Date & Horaire</th>
                    <th>Remplissage</th>
                    <th>Recette</th>
                </tr>
            </thead>
            <tbody>
    `;

    jauges.forEach(j => {
        const vendues = j.places_vendues || 0;
        const capacite = j.capacite || 1;
        const pourcentage = Math.min(Math.round((vendues / capacite) * 100), 100);
        const recette = (j.recette || 0).toFixed(2);

        html += `
            <tr>
                <td data-label="Soirée :"><strong>${j.titre}</strong></td>
                <td data-label="Date :">${j.date} à ${j.horaire}</td>
                <td data-label="Remplissage :">
                    <div class="progress-bar-container">
                        <div class="progress-fill" style="width: ${pourcentage}%"></div>
                        <div class="progress-text">${vendues} / ${capacite} (${pourcentage}%)</div>
                    </div>
                </td>
                <td data-label="Recette :">${recette} €</td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
    `;

    mainContent.innerHTML = html;
}