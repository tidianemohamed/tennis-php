<?php

namespace App\Controllers;

use App\Models\TournamentMatch;
use App\Models\Player;
use App\Models\Tournament;
use App\Database\DB;
use PDO;

class MatchController
{
    public function index()
    {
        if (ob_get_length()) ob_clean(); // Evita che errori PHP sporchino il JSON
        header('Content-Type: application/json');
        
        $tournamentId = $_GET['tournament_id'] ?? null;

        if (!$tournamentId) {
            echo json_encode([]);
            exit;
        }

        try {
            $db = DB::getInstance()->getConnection();

            $sql = "SELECT 
                        m.*,
                        p1.first_name as p1_fn, p1.last_name as p1_ln, p1.country as p1_c,
                        p2.first_name as p2_fn, p2.last_name as p2_ln, p2.country as p2_c
                    FROM tournament_matches m
                    LEFT JOIN players p1 ON m.player1_id = p1.id
                    LEFT JOIN players p2 ON m.player2_id = p2.id
                    WHERE m.tournament_id = ?
                    ORDER BY m.id ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute([(int)$tournamentId]);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results = array_map(function($m) {
                // Combiniamo i nomi e gestiamo i valori nulli per evitare crash in React
                $p1Name = trim(($m['p1_fn'] ?? '') . " " . ($m['p1_ln'] ?? ''));
                $p2Name = trim(($m['p2_fn'] ?? '') . " " . ($m['p2_ln'] ?? ''));

                return [
                    "id" => (int)$m['id'],
                    "tournament_id" => (int)$m['tournament_id'],
                   
                    "round" => $m['round_name'], 
                    "player1_id" => $m['player1_id'] ? (int)$m['player1_id'] : null,
                    "player2_id" => $m['player2_id'] ? (int)$m['player2_id'] : null,
                    "player1" => $p1Name ?: "TBD",
                    "player2" => $p2Name ?: "TBD",
                    "p1_country" => $m['p1_c'] ?? 'un', // 'un' come default (unknown)
                    "p2_country" => $m['p2_c'] ?? 'un',
                    "player1_score" => (int)($m['player1_score'] ?? 0),
                    "player2_score" => (int)($m['player2_score'] ?? 0),
                    "winner_id" => $m['winner_player_id'] ? (int)$m['winner_player_id'] : null,
                    "status" => $m['status'] ?? 'pending',
                    "next_match_id" => $m['next_match_id'] ? (int)$m['next_match_id'] : null
                ];
            }, $matches);

            echo json_encode($results);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        exit;
    }

    public function update(int $matchId)
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $db = DB::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM tournament_matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$match) throw new \Exception("Match non trovato");

            $p1Score = (int)($data['player1_score'] ?? 0);
            $p2Score = (int)($data['player2_score'] ?? 0);
            
            if ($p1Score === $p2Score) throw new \Exception("Pareggio non ammesso");

            $winnerId = ($p1Score > $p2Score) ? $match['player1_id'] : $match['player2_id'];

            // Aggiornamento match corrente
            $stmtUpdate = $db->prepare("UPDATE tournament_matches SET player1_score = ?, player2_score = ?, winner_player_id = ?, status = 'completed' WHERE id = ?");
            $stmtUpdate->execute([$p1Score, $p2Score, $winnerId, $matchId]);

            // Avanzamento al match successivo
            if ($match['next_match_id']) {
                $nextId = (int)$match['next_match_id'];
                
                // Controlliamo se occupare player1 o player2 nel match successivo
                $stmtCheck = $db->prepare("SELECT player1_id FROM tournament_matches WHERE id = ?");
                $stmtCheck->execute([$nextId]);
                $next = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if (empty($next['player1_id'])) {
                    $db->prepare("UPDATE tournament_matches SET player1_id = ? WHERE id = ?")->execute([$winnerId, $nextId]);
                } else {
                    $db->prepare("UPDATE tournament_matches SET player2_id = ? WHERE id = ?")->execute([$winnerId, $nextId]);
                }
            }

            // Finale -> Vincitore Torneo
            if (stripos($match['round_name'], 'finale') !== false && stripos($match['round_name'], 'semi') === false) {
                $db->prepare("UPDATE tournaments SET status = 'completed', winner_player_id = ? WHERE id = ?")
                   ->execute([$winnerId, $match['tournament_id']]);
            }

            echo json_encode(["status" => "success", "winner_id" => $winnerId]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        exit;
    }
}