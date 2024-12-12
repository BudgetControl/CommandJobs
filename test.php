<?php

// Configurazione MySQL
$mysqlHost = 'dummy_db';
$mysqlUser = 'root';
$mysqlPassword = 'root';
$mysqlDatabase = 'budgetV2';

// Configurazione PostgreSQL
$pgHost = 'budgetcontrol-db';
$pgUser = 'username';
$pgPassword = 'passwordusername';
$pgDatabase = 'budgetV2';


try {
    // Connessione a MySQL
    $mysqlConn = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDatabase;charset=utf8mb4", $mysqlUser, $mysqlPassword);
    $mysqlConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Connessione a PostgreSQL
    $pgConn = new PDO("pgsql:host=$pgHost;dbname=$pgDatabase", $pgUser, $pgPassword);
    $pgConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connessioni ai database stabilite.\n";

    // Ottenere l'elenco delle tabelle da MySQL
    $tables = ['users', 'categories', 'payments_types', 'currencies', 'workspaces', 'labels', 'wallets', 'entries', 'payees', 'models', 'planned_entries', 'budgets', 'entry_labels', 'planned_entry_labels', 'model_labels', 'workspaces_users_mm','workspace_settings','sub_categories'];

    foreach ($tables as $table) {
        echo "Esportazione dati dalla tabella: $table\n";

        // Estrarre i dati da MySQL
        $data = $mysqlConn->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            echo "Nessun dato trovato nella tabella: $table\n";
            continue;
        }

        // Generare gli statement di insert per PostgreSQL
        $columns = array_keys($data[0]);
        $columnList = '"' . implode('", "', $columns) . '"';
        $placeholders = ':' . implode(', :', $columns);

        $insertQuery = "INSERT INTO \"$table\" ($columnList) VALUES ($placeholders)";
        $stmt = $pgConn->prepare($insertQuery);

        foreach ($data as $row) {
            $params = [];
            foreach ($row as $key => $value) {
                // Gestire eventuali valori null
                $params[":$key"] = $value !== null ? $value : null;
            }
            $stmt->execute($params);
        }

        echo "Dati inseriti nella tabella PostgreSQL: $table\n";
    }

    echo "Migrazione completata con successo.\n";
} catch (PDOException $e) {
    echo json_encode($params) . "\n";
    echo "Errore: " . $e->getMessage() . "\n";
} finally {
    $mysqlConn = null;
    $pgConn = null;
}
