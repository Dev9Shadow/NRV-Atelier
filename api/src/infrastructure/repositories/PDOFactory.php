<?php
namespace nrv\infrastructure\repositories;

use PDO;

class PDOFactory
{
    /** Connexion à partir des variables d'environnement (définies dans docker-compose.yml) */
    public static function create(): PDO
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            getenv('DB_HOST') ?: 'db',
            getenv('DB_PORT') ?: '5432',
            getenv('DB_NAME') ?: 'nrv_db'
        );

        return new PDO($dsn, getenv('DB_USER') ?: 'nrv_user', getenv('DB_PASSWORD') ?: 'nrv_password', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
