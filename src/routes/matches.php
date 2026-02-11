<?php

use App\Controllers\MatchController;

return function($uri, $method) {
    if (strpos($uri, '/api/matches') !== 0) {
        return false;
    }

    $controller = new MatchController();

    // POST /api/matches/{id} o GET /api/matches/{id}
    if (preg_match('#^/api/matches/(\d+)$#', $uri, $matches)) {
        $matchId = (int)$matches[1];
        if ($method === 'POST') $controller->update($matchId);
        else $controller->show($matchId);
        return true;
    }

    // GET /api/matches
    if ($uri === '/api/matches' && $method === 'GET') {
        $controller->index();
        return true;
    }

    return false;
};