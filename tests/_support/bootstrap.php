<?php

/**
 * Bootstrap PHPUnit aplikasi: meneruskan bootstrap bawaan framework
 * lalu menyiapkan tabel infrastruktur paket Settings (contextual
 * settings) pada database :memory: tests. Migrasi paket vendor tidak
 * pernah berjalan di lingkungan test, sedangkan helper setting()
 * dapat dipanggil kapan saja oleh Shield.
 */

require dirname(__DIR__) . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

if (ENVIRONMENT === 'testing' && extension_loaded('sqlite3')) {
    $db = Config\Database::connect('tests');

    $db->query('CREATE TABLE IF NOT EXISTS "db_contextual_settings" (
        "id" INTEGER PRIMARY KEY AUTOINCREMENT,
        "class" VARCHAR(255) NOT NULL,
        "key" VARCHAR(255) NOT NULL,
        "value" TEXT,
        "type" VARCHAR(31) NOT NULL DEFAULT \'string\',
        "context" VARCHAR(255),
        "created_at" DATETIME NOT NULL,
        "updated_at" DATETIME NOT NULL
    )');
}
