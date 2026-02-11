<?php

namespace App\Controllers;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\Player;
use App\Database\DB;
use PDO;

class TournamentController
{
    /**
     * Lista tutti i tornei
     */
   public function index()
{
    // Puliamo eventuali buffer di uscita per evitare che avvisi PHP "sporchino" il JSON
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    
    try {
        $db = DB::getInstance()->getConnection();
        
        // Prendiamo i tornei ordinati dal più recente
        $stmt = $db->query("SELECT * FROM tournaments ORDER BY id DESC");
        $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = array_map(function($t) use ($db) {
            $winnerName = "In corso...";
            
            // Gestione del vincitore con i nuovi campi nome/cognome
            if (!empty($t['winner_player_id'])) {
                $stmtP = $db->prepare("SELECT first_name, last_name FROM players WHERE id = ?");
                $stmtP->execute([$t['winner_player_id']]);
                $p = $stmtP->fetch(PDO::FETCH_ASSOC);
                
                if ($p) {
                    $fn = $p['first_name'] ?? '';
                    $ln = $p['last_name'] ?? '';
                    $winnerName = trim($fn . " " . $ln);
                }
            }

            return [
                "id"          => (int)$t['id'],
                "name"        => $t['name'] ?? 'Senza Nome',
                "status"      => $t['status'] ?? 'draft',
                "date"        => $t['date'] ?? '',
                "location"    => $t['location'] ?? '',
                "winner_id"   => $t['winner_player_id'],
                "winner_name" => (!empty($winnerName) && $winnerName !== " ") ? $winnerName : "In corso..."
            ];
        }, $tournaments);

        echo json_encode(array_values($results));
        exit;
        
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
        exit;
    }
}
    /**
     * Mostra dettagli singolo torneo
     */
  public function show(int $id)
{
    header('Content-Type: application/json');
    try {
        $tournament = Tournament::find($id);
        
        if ($tournament) {
            $db = DB::getInstance()->getConnection();
            $winnerName = "In corso...";
            
            // Cerchiamo il nome del vincitore se presente
            if (!empty($tournament->winner_player_id)) {
                $stmtP = $db->prepare("SELECT first_name, last_name FROM players WHERE id = ?");
                $stmtP->execute([$tournament->winner_player_id]);
                $p = $stmtP->fetch(PDO::FETCH_ASSOC);
                if ($p) {
                    $winnerName = trim(($p['first_name'] ?? '') . " " . ($p['last_name'] ?? ''));
                }
            }

            // Costruiamo manualmente l'array di risposta per evitare l'errore toArray()
            $response = [
                "id" => (int)$tournament->id,
                "name" => $tournament->name,
                "status" => $tournament->status,
                "date" => $tournament->date,
                "location" => $tournament->location,
                "winner_player_id" => $tournament->winner_player_id,
                "winner_name" => $winnerName
            ];
            
            echo json_encode($response);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Torneo non trovato"]);
        }
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Errore caricamento torneo: " . $e->getMessage()]);
    }
    exit;
}

    /**
     * Crea un nuovo torneo
     */
    public function store()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $tournament = new Tournament();
            $tournament->name = $data['name'] ?? 'Nuovo Torneo';
            $tournament->date = $data['date'] ?? date('Y-m-d'); 
            $tournament->location = $data['location'] ?? 'Non specificato';
            $tournament->status = 'draft';
            $tournament->save();
            echo json_encode(["status" => "success", "tournament" => $tournament]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * GENERA IL TABELLONE (8 Giocatori)
     */
    public function generate(int $tId)
{
    header('Content-Type: application/json');
    try {
        $db = DB::getInstance()->getConnection();

        // 1. Pulizia match esistenti
        $db->prepare("DELETE FROM tournament_matches WHERE tournament_id = ?")->execute([$tId]);

        // 2. Recupero giocatori
        $stmt = $db->prepare("SELECT id FROM players WHERE tournament_id = ? AND deleted_at IS NULL");
        $stmt->execute([$tId]);
        $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($players) < 8) {
            http_response_code(400);
            echo json_encode(["error" => "Servono 8 giocatori."]);
            return;
        }
        
        shuffle($players);

        // 3. Creazione struttura
        $finaleId = $this->createMatchSql($tId, 'Finale', null, null, null);
        $s1Id = $this->createMatchSql($tId, 'Semifinale 1', null, null, $finaleId);
        $s2Id = $this->createMatchSql($tId, 'Semifinale 2', null, null, $finaleId);
        
        $this->createMatchSql($tId, 'Quarto 1', $players[0]['id'], $players[1]['id'], $s1Id);
        $this->createMatchSql($tId, 'Quarto 2', $players[2]['id'], $players[3]['id'], $s1Id);
        $this->createMatchSql($tId, 'Quarto 3', $players[4]['id'], $players[5]['id'], $s2Id);
        $this->createMatchSql($tId, 'Quarto 4', $players[6]['id'], $players[7]['id'], $s2Id);

        // 4. Update status
        $db->prepare("UPDATE tournaments SET status = 'active' WHERE id = ?")->execute([$tId]);

        // RESTITUIAMO SOLO QUESTO
        echo json_encode(["status" => "success", "message" => "Tabellone creato"]);
        exit; 
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }
}

    /**
     * Helper SQL diretto per i Match
     */
    private function createMatchSql($tId, $roundName, $p1, $p2, $nextMatchId) {
        $db = DB::getInstance()->getConnection();
        $sql = "INSERT INTO tournament_matches 
                (tournament_id, round_name, player1_id, player2_id, next_match_id, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $db->prepare($sql);
        
        // bindValue gestisce correttamente i valori NULL per PostgreSQL
        $stmt->bindValue(1, $tId, PDO::PARAM_INT);
        $stmt->bindValue(2, $roundName, PDO::PARAM_STR);
        $stmt->bindValue(3, $p1, $p1 === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(4, $p2, $p2 === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(5, $nextMatchId, $nextMatchId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        
        $stmt->execute();
        return $db->lastInsertId();
    }

    /**
     * Elimina torneo
     */
    public function destroy(int $id)
    {
        header('Content-Type: application/json');
        try {
            $db = DB::getInstance()->getConnection();
            $stmt1 = $db->prepare("DELETE FROM tournament_matches WHERE tournament_id = ?");
            $stmt1->execute([$id]);
            $stmt2 = $db->prepare("DELETE FROM tournaments WHERE id = ?");
            $stmt2->execute([$id]);
            echo json_encode(["status" => "success"]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        exit;
    }
}