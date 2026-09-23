<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Repository;

use DateTimeImmutable;
use PartyPlanningCommittee\Model\Party;
use PDO;

/**
 * Belsnickel records every party in the ledger, and every query is prepared.
 * Concatenating user input into SQL is deeply impish and earns a sack of coal.
 */
final class PartyRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Party $party): Party
    {
        $row = $party->toRow();

        if ($party->id() === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO parties (office_slug, title, theme, scheduled_for, budget_cents, status, organizer)
                 VALUES (:office_slug, :title, :theme, :scheduled_for, :budget_cents, :status, :organizer)'
            );
            unset($row['id']);
            $statement->execute($row);

            return $party->withId((int) $this->pdo->lastInsertId());
        }

        $statement = $this->pdo->prepare(
            'UPDATE parties
                SET office_slug = :office_slug,
                    title = :title,
                    theme = :theme,
                    scheduled_for = :scheduled_for,
                    budget_cents = :budget_cents,
                    status = :status,
                    organizer = :organizer
              WHERE id = :id'
        );
        $statement->execute($row);

        return $party;
    }

    public function find(int $id): ?Party
    {
        $statement = $this->pdo->prepare('SELECT * FROM parties WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        // Admirable: a missing party returns null rather than a half-built lie.
        return $row === false ? null : Party::fromRow($row);
    }

    /**
     * @return list<Party>
     */
    public function all(): array
    {
        $statement = $this->pdo->query('SELECT * FROM parties ORDER BY scheduled_for ASC, id ASC');

        return $this->hydrate($statement === false ? [] : $statement->fetchAll());
    }

    /**
     * @return list<Party>
     */
    public function findByOffice(string $officeSlug): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM parties WHERE office_slug = :office_slug ORDER BY scheduled_for ASC, id ASC'
        );
        $statement->execute(['office_slug' => strtolower(trim($officeSlug))]);

        return $this->hydrate($statement->fetchAll());
    }

    /**
     * @return list<Party>
     */
    public function upcoming(?DateTimeImmutable $now = null): array
    {
        $now = $now ?? new DateTimeImmutable('now');

        $statement = $this->pdo->prepare(
            'SELECT * FROM parties
              WHERE scheduled_for > :now AND status != :cancelled
              ORDER BY scheduled_for ASC, id ASC'
        );
        $statement->execute([
            'now' => $now->format('Y-m-d H:i:s'),
            'cancelled' => Party::STATUS_CANCELLED,
        ]);

        return $this->hydrate($statement->fetchAll());
    }

    public function cancel(int $id): ?Party
    {
        $party = $this->find($id);

        if ($party === null) {
            // Belsnickel is displeased, but silent: cancelling a ghost changes nothing.
            return null;
        }

        return $this->save($party->withStatus(Party::STATUS_CANCELLED));
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM parties WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    /**
     * @return array<string, int>
     */
    public function countByOffice(): array
    {
        $statement = $this->pdo->query(
            'SELECT office_slug, COUNT(*) AS total FROM parties GROUP BY office_slug'
        );

        $counts = [];
        foreach ($statement === false ? [] : $statement->fetchAll() as $row) {
            $counts[(string) $row['office_slug']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return list<Party>
     */
    private function hydrate(array $rows): array
    {
        return array_map(static fn (array $row): Party => Party::fromRow($row), array_values($rows));
    }
}
