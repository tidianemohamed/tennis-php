<?php
// config/database.php

$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    // Se siamo su Railway, estraiamo i dati dalla stringa magica
    $dbconnections = parse_url($databaseUrl);
    
    return [
        'host'     => $dbconnections['host'],
        'port'     => $dbconnections['port'] ?? '5432',
        'database' => ltrim($dbconnections['path'], '/'),
        'username' => $dbconnections['user'],
        'password' => $dbconnections['pass'],
    ];
}

// Se siamo in locale sul Mac, usiamo i tuoi dati originali
return [
    'host'     => 'localhost',
    'port'     => '5432',
    'database' => 'tennis',
    'username' => 'postgres',
    'password' => 'Amine2004!',
];