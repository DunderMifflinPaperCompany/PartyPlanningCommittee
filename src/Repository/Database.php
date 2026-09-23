<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Repository;

use PDO;

/**
 * Belsnickel opens the ledger. SQLite is humble, but honest, and honesty is admirable.
 */
final class Database
{
    /**
     * Belsnickel insists on exceptions: an error that passes unnoticed is the most impish error of all.
     */
    public static function connect(string $dsn): PDO
    {
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    public static function inMemory(): PDO
    {
        $pdo = self::connect('sqlite::memory:');
        self::migrate($pdo);

        return $pdo;
    }

    public static function file(string $path): PDO
    {
        $pdo = self::connect('sqlite:' . $path);
        self::migrate($pdo);

        return $pdo;
    }

    /**
     * Admirable: the schema is created once, idempotently, and RSVPs die with their party.
     */
    public static function migrate(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS parties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                office_slug TEXT NOT NULL,
                title TEXT NOT NULL,
                theme TEXT NOT NULL,
                scheduled_for TEXT NOT NULL,
                budget_cents INTEGER NOT NULL,
                status TEXT NOT NULL,
                organizer TEXT NOT NULL
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS rsvps (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                party_id INTEGER NOT NULL,
                employee_name TEXT NOT NULL,
                status TEXT NOT NULL,
                dish TEXT NOT NULL DEFAULT "",
                FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE CASCADE
            )'
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_parties_office ON parties(office_slug)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rsvps_party ON rsvps(party_id)');
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_rsvps_unique_guest ON rsvps(party_id, employee_name)');
    }
}
