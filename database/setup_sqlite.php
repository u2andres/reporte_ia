<?php

/**
 * Construye database/database.sqlite a partir del dump database/database.sqlite.sql.
 *
 * Reemplaza al "php artisan migrate" dentro de "composer setup": la base ya viene
 * armada (sus datos fueron copiados desde la conexion 'doctrine' mediante las
 * migraciones fill_*), por lo que en el setup NO se regenera por migraciones sino
 * que se importa el dump directamente.
 *
 * Uso: php database/setup_sqlite.php
 */

$dir  = __DIR__;
$db   = $dir . DIRECTORY_SEPARATOR . 'database.sqlite';
$dump = $dir . DIRECTORY_SEPARATOR . 'database.sqlite.sql';

if (!file_exists($dump)) {
    fwrite(STDERR, "ERROR: no se encontro el dump: {$dump}\n");
    exit(1);
}

// Crea el archivo si no existe (en un checkout limpio no estara).
if (!file_exists($db)) {
    touch($db);
}

try {
    $pdo = new PDO('sqlite:' . $db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Limpia las tablas existentes para una importacion limpia e idempotente
    // (el dump usa CREATE TABLE IF NOT EXISTS + INSERT; correrlo sobre una base
    //  ya poblada duplicaria filas / violaria PKs). Se hace por PDO en vez de
    //  borrar el archivo, que en Windows falla si otro proceso lo tiene abierto.
    $tables = $pdo->query(
        "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
    )->fetchAll(PDO::FETCH_COLUMN);
    $pdo->exec('PRAGMA foreign_keys = OFF');
    foreach ($tables as $t) {
        $pdo->exec('DROP TABLE IF EXISTS "' . $t . '"');
    }

    $pdo->exec(file_get_contents($dump));
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR al importar el dump: " . $e->getMessage() . "\n");
    exit(1);
}

echo "OK: database.sqlite armado desde database.sqlite.sql\n";
