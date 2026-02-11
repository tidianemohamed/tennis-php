<?php

use App\Controllers\TournamentController;

return function($uri, $method) {
    // 🔍 DEBUG
    error_log("🎯 TOURNAMENTS ROUTE | URI: $uri | METHOD: $method");
    
    if (strpos($uri, '/api/tournaments') !== 0) {
        error_log("❌ Non inizia con /api/tournaments");
        return false;
    }

    error_log("✅ Inizia con /api/tournaments, procedo...");
    
    $controller = new TournamentController();

    // POST /api/tournaments/{id}/generate
    if (preg_match('#^/api/tournaments/(\d+)/generate$#', $uri, $matches) && $method === 'POST') {
        error_log("✅ Match: generate");
        $controller->generate((int)$matches[1]);
        return true;
    }

    // GET /api/tournaments/{id} o DELETE /api/tournaments/{id}
    if (preg_match('#^/api/tournaments/(\d+)$#', $uri, $matches)) {
        error_log("✅ Match: single tournament");
        $id = (int)$matches[1];
        if ($method === 'GET') $controller->show($id);
        elseif ($method === 'DELETE') $controller->destroy($id);
        return true;
    }

    // GET /api/tournaments o POST /api/tournaments
    if ($uri === '/api/tournaments') {
        error_log("✅ Match: all tournaments");
        if ($method === 'POST') $controller->store();
        else $controller->index();
        return true;
    }

    error_log("❌ Nessun match trovato");
    return false;
};