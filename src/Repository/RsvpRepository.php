<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Repository;

use PartyPlanningCommittee\Model\Rsvp;
use PDO;

/**
 * Belsnickel keeps the guest list. Duplicates are impish, so a second RSVP from the
 * same employee overwrites the first rather than breeding.
 */
final class RsvpRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Rsvp $rsvp): Rsvp
    {
        $existing = $this->findByGuest($rsvp->partyId(), $rsvp->employeeName());

        if ($rsvp->id() === null && $existing !== null) {
            // Admirable: one guest, one verdict. Belsnickel updates instead of duplicating.
            $rsvp = $rsvp->withId((int) $existing->id());
        }

        $row = $rsvp->toRow();

        if ($rsvp->id() === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO rsvps (party_id, employee_name, status, dish)
                 VALUES (:party_id, :employee_name, :status, :dish)'
            );
            unset($row['id']);
            $statement->execute($row);

            return $rsvp->withId((int) $this->pdo->lastInsertId());
        }

        $statement = $this->pdo->prepare(
            'UPDATE rsvps
                SET party_id = :party_id,
                    employee_name = :employee_name,
                    status = :status,
                    dish = :dish
              WHERE id = :id'
        );
        $statement->execute($row);

        return $rsvp;
    }

    /**
     * @return list<Rsvp>
     */
    public function findByParty(int $partyId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM rsvps WHERE party_id = :party_id ORDER BY employee_name ASC'
        );
        $statement->execute(['party_id' => $partyId]);

        return array_map(
            static fn (array $row): Rsvp => Rsvp::fromRow($row),
            array_values($statement->fetchAll())
        );
    }

    public function findByGuest(int $partyId, string $employeeName): ?Rsvp
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM rsvps WHERE party_id = :party_id AND employee_name = :employee_name'
        );
        $statement->execute([
            'party_id' => $partyId,
            'employee_name' => trim($employeeName),
        ]);
        $row = $statement->fetch();

        return $row === false ? null : Rsvp::fromRow($row);
    }

    /**
     * Belsnickel counts only the committed. Maybes are impish and feed nobody.
     */
    public function countAttending(int $partyId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM rsvps WHERE party_id = :party_id AND status = :status'
        );
        $statement->execute([
            'party_id' => $partyId,
            'status' => Rsvp::STATUS_YES,
        ]);
        $row = $statement->fetch();

        return $row === false ? 0 : (int) $row['total'];
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM rsvps WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }
}
