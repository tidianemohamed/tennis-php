<?php

use App\Controllers\PlayerController;

return function($uri, $method) {
    $controller = new PlayerController();

    // GET /api/players/history
    if ($uri === '/api/players/history') {
        $controller->getHistory();
        return true;
    }

    // DELETE /api/players/{id} o PUT /api/players/{id}
    if (preg_match('#^/api/players/(\d+)$#', $uri, $matches)) {
        $id = (int)$matches[1];
        if ($method === 'DELETE') $controller->destroy($id);
        elseif ($method === 'PUT') $controller->update($id);
        return true;
    }

    // GET /api/players o POST /api/players
    if ($uri === '/api/players') {
        if ($method === 'GET') $controller->index();
        elseif ($method === 'POST') $controller->store();
        return true;
    }

    return false; // Rotta non gestita
};