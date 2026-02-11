<?php

namespace App\Models;

class TournamentMatch extends BaseModel
{
    protected static string $table = 'matches';

    public ?int $id = null; 
    public int $tournament_id;
    public string $round_name;
    public ?int $player1_id = null;
    public ?int $player2_id = null;
    public ?int $player1_score = null;
    public ?int $player2_score = null;
    public ?int $winner_id = null;
    public ?int $next_match_id = null;
    public string $status;

    public function updateScore(int $s1, int $s2)
    {
        $this->player1_score = $s1;
        $this->player2_score = $s2;
        
        if ($s1 > $s2) {
            $this->winner_id = $this->player1_id;
        } elseif ($s2 > $s1) {
            $this->winner_id = $this->player2_id;
        }

        $this->status = 'completed';
        $this->save(); 

        if ($this->winner_id && $this->next_match_id) {
            $nextMatch = self::find($this->next_match_id);
            if ($nextMatch) {
                if (is_null($nextMatch->player1_id)) {
                    $nextMatch->player1_id = $this->winner_id;
                } else {
                    $nextMatch->player2_id = $this->winner_id;
                }
                $nextMatch->save();
            }
        }
        return $this->winner_id;
    }
}