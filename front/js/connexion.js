import { connecter, inscrire } from './auth.js';

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    const urlParams = new URLSearchParams(window.location.search);
    const retour = urlParams.get('retour') || 'index.html';

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            const errorDiv = document.getElementById('login-error');

            try {
                const success = await connecter(email, password);
                if (success) {
                    window.location.href = retour;
                }
            } catch (err) {
                errorDiv.textContent = err.message || "Identifiants incorrects.";
                errorDiv.classList.add('visible');
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const nom = document.getElementById('register-nom').value;
            const prenom = document.getElementById('register-prenom').value;
            const email = document.getElementById('register-email').value;
            const password = document.getElementById('register-password').value;
            const errorDiv = document.getElementById('register-error');

            try {
                await inscrire(nom, prenom, email, password);
                await connecter(email, password);
                window.location.href = retour;
            } catch (err) {
                errorDiv.textContent = err.message || "Erreur lors de l'inscription.";
                errorDiv.classList.add('visible');
            }
        });
    }
});