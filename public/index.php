<?php
/**
 * Punto di ingresso dell'applicazione Tennis Tournament
 * Smista le richieste verso il bootstrap del sistema.
 */

// 1. Recuperiamo il percorso della richiesta (es: /api/players)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 2. Se la richiesta è per un file fisico che esiste davvero (es: un .json o .css), servilo direttamente
// Altrimenti, tutto passa per il bootstrap.php
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

// 3. Carichiamo il bootstrap che contiene la logica delle API e l'autoload
require __DIR__ . '/../src/bootstrap.php';