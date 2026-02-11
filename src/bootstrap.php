<?php




require_once __DIR__ . '/../vendor/autoload.php';



header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gestione richieste OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Carica tutti i file delle rotte dalla cartella routes/
    $routeFiles = [
        __DIR__ . '/routes/players.php',
        __DIR__ . '/routes/tournaments.php',
        __DIR__ . '/routes/matches.php',
    ];

    // Esegui ogni file di rotte fino a trovare una corrispondenza
    foreach ($routeFiles as $routeFile) {
        if (file_exists($routeFile)) {
            $routeHandler = require $routeFile;
            if ($routeHandler($uri, $method)) {
                exit; // ✅ Rotta trovata e gestita
            }
        }
    }

    //  Nessuna rotta trovata
    http_response_code(404);
    echo json_encode(["error" => "Percorso non trovato: $uri"]);

} catch (\Exception $e) {
    // ⚠️ Gestione errori globali
    http_response_code(500);
    echo json_encode(["error" => "Errore server", "message" => $e->getMessage()]);
}