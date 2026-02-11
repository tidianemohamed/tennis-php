<?php

namespace App\Controllers;

use App\Models\Player;
use App\Database\DB;
use PDO;

class PlayerController
{
    public function index()
    {
        header('Content-Type: application/json');
        try {
            $tId = $_GET['tournament_id'] ?? null;
            $db = DB::getInstance()->getConnection();

            if ($tId) {
                $stmt = $db->prepare("SELECT * FROM players WHERE tournament_id = ? AND deleted_at IS NULL ORDER BY id ASC");
                $stmt->execute([$tId]);
                $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $db->query("SELECT * FROM players WHERE deleted_at IS NULL");
                $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $formattedPlayers = array_map(function($p) {
                $p['name'] = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                return $p;
            }, $players);

            echo json_encode($formattedPlayers);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

public function getHistory()
{
    header('Content-Type: application/json');
    try {
        $db = DB::getInstance()->getConnection();
        
        // Prendiamo il MAX(id) come ID di riferimento per ogni giocatore unico
        $sql = "SELECT MAX(id) as id, first_name, last_name, country 
                FROM players 
                WHERE deleted_at IS NULL 
                GROUP BY first_name, last_name, country 
                ORDER BY id DESC"; 
        
        $stmt = $db->query($sql);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($history ? $history : []);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
  public function store()
{
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $tournamentId = $data['tournament_id'] ?? null;
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');

    try {
        $db = DB::getInstance()->getConnection();

        // 1. CONTROLLO MASSIMO 8 GIOCATORI
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM players WHERE tournament_id = ? AND deleted_at IS NULL");
        $stmtCheck->execute([$tournamentId]);
        if ($stmtCheck->fetchColumn() >= 8) {
            http_response_code(400);
            echo json_encode(["error" => "Il torneo ha già raggiunto il limite massimo di 8 giocatori."]);
            exit;
        }

        // 2. CONTROLLO DUPLICATI (Novità)
        // Verifichiamo se esiste già un giocatore attivo con lo stesso nome e cognome nel torneo
        $stmtDup = $db->prepare("SELECT COUNT(*) FROM players 
                                 WHERE tournament_id = ? 
                                 AND first_name = ? 
                                 AND last_name = ? 
                                 AND deleted_at IS NULL");
        $stmtDup->execute([$tournamentId, $firstName, $lastName]);
        
        if ($stmtDup->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(["error" => "Questo giocatore è già iscritto a questo torneo."]);
            exit;
        }

        // 3. SE TUTTO OK, INSERIAMO
        $sql = "INSERT INTO players (first_name, last_name, country, tournament_id) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$firstName, $lastName, $data['country'] ?? 'IT', $tournamentId]);

        echo json_encode(["status" => "success"]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

    /**
     * MODIFICA: Aggiorna i dati di un giocatore esistente
     */
    public function update($id)
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $db = DB::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE players SET first_name = ?, last_name = ?, country = ? WHERE id = ?");
            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['country'],
                $id
            ]);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * ELIMINA: Rimuove un giocatore (Soft Delete o Hard Delete)
     */
public function destroy($id)
{
    header('Content-Type: application/json');
    try {
        $db = DB::getInstance()->getConnection();

        // 1. CONTROLLO PROTEZIONE (Basato sul tuo MatchController)
        // Usiamo la tabella 'tournament_matches' e le colonne 'player1_id'/'player2_id'
        $sqlCheck = "SELECT COUNT(*) FROM tournament_matches WHERE player1_id = ? OR player2_id = ?";
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->execute([$id, $id]);
        $countInMatches = $stmtCheck->fetchColumn();

        if ($countInMatches > 0) {
            // Se il giocatore è in almeno un match, blocchiamo l'eliminazione
            http_response_code(400); 
            echo json_encode([
                "error" => "Non puoi eliminare questo giocatore: è già inserito in un tabellone e ha dei match programmati o giocati."
            ]);
            exit;
        }

        // 2. SE IL CONTROLLO PASSA, ELIMINIAMO (o Soft Delete)
        // Se usi la colonna deleted_at come abbiamo visto prima:
        $stmt = $db->prepare("UPDATE players SET deleted_at = NOW() WHERE id = ?");
        
        // Oppure se vuoi cancellare proprio la riga:
        // $stmt = $db->prepare("DELETE FROM players WHERE id = ?");
        
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
}