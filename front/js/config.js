// Configuration du client NRV.net

// true  : les données viennent des fichiers de front/mocks/ (pas besoin de l'API)
// false : les données viennent de l'API Slim
export const USE_MOCKS = true;

// Adresse de l'API (port 55000 du docker compose). À adapter pour docketu.
export const API_BASE = 'http://localhost:55000';
