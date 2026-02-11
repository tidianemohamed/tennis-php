## 📦 Installazione

```bash

# 1. Installa le dipendenze
composer install

# 2. Configura il database
# (crea il file di configurazione e il database)

# 3. Avvia il server
php -S localhost:8080 -t public


## 📁 Struttura del Progetto

tennis.php/
├── src/
│   ├── bootstrap.php               # Entry point e orchestratore
│   ├── routes/                     # File delle rotte
│   │   ├── players.php             # Rotte giocatori
│   │   ├── tournaments.php         # Rotte tornei
│   │   └── matches.php             # Rotte partite
│   ├── Controllers/                # Controller MVC
│   │   ├── PlayerController.php
│   │   ├── TournamentController.php
│   │   └── MatchController.php
│   ├── Models/                     # Modelli dati
│   │   ├── BaseModel.php           # Model base con logica comune
│   │   ├── Player.php              # Model giocatore
│   │   ├── Tournament.php          # Model torneo
│   │   └── TournamentMatch.php     # Model partita
│   └── Database/                   # Gestione database
│       └── DB.php                  # Classe per connessione DB
├── vendor/                         # Dipendenze Composer
├── public/                         # File pubblici
│   └── index.php                  # Front controller
├── composer.json
└── README.md


### 📝 Dettaglio Routes

| **players.php** | Gestione completa giocatori | `GET`, `POST`, `PUT`, `DELETE` su `/api/players` + `/api/players/history` |

| **tournaments.php** | Gestione tornei e generazione bracket | `GET`, `POST`, `DELETE` su `/api/tournaments` + `POST /api/tournaments/{id}/generate` |

| **matches.php** | Visualizzazione e aggiornamento risultati | `GET` su `/api/matches` + `POST` per aggiornare punteggi |