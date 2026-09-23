<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Model;

use InvalidArgumentException;

/**
 * Belsnickel judges an Rsvp. No RSVP, no entry. That rule has never bent.
 */
final class Rsvp
{
    public const STATUS_YES = 'yes';
    public const STATUS_NO = 'no';
    public const STATUS_MAYBE = 'maybe';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_YES, self::STATUS_NO, self::STATUS_MAYBE];

    private ?int $id;
    private int $partyId;
    private string $employeeName;
    private string $status;
    private string $dish;

    public function __construct(
        ?int $id,
        int $partyId,
        string $employeeName,
        string $status,
        string $dish = ''
    ) {
        $employeeName = trim($employeeName);
        $dish = trim($dish);

        if ($id !== null && $id < 1) {
            throw new InvalidArgumentException('Impish identifier! Belsnickel counts RSVPs from one.');
        }

        if ($partyId < 1) {
            throw new InvalidArgumentException('Impish RSVP! It must belong to a real party.');
        }

        if ($employeeName === '') {
            throw new InvalidArgumentException('Impish guest! No RSVP, no entry.');
        }

        if (mb_strlen($employeeName) > 80) {
            throw new InvalidArgumentException('Impish name! Belsnickel stops reading after 80 characters.');
        }

        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Impish RSVP status! Belsnickel accepts only: ' . implode(', ', self::STATUSES) . '.');
        }

        if (mb_strlen($dish) > 80) {
            throw new InvalidArgumentException('Impish dish! Belsnickel stops reading after 80 characters.');
        }

        $this->id = $id;
        $this->partyId = $partyId;
        $this->employeeName = $employeeName;
        $this->status = $status;
        $this->dish = $dish;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function partyId(): int
    {
        return $this->partyId;
    }

    public function employeeName(): string
    {
        return $this->employeeName;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function dish(): string
    {
        return $this->dish;
    }

    // Admirable: only a firm "yes" counts toward the head count. Maybes feed nobody.
    public function isAttending(): bool
    {
        return $this->status === self::STATUS_YES;
    }

    public function withId(int $id): self
    {
        return new self($id, $this->partyId, $this->employeeName, $this->status, $this->dish);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (int) $row['party_id'],
            (string) $row['employee_name'],
            (string) $row['status'],
            (string) ($row['dish'] ?? '')
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'party_id' => $this->partyId,
            'employee_name' => $this->employeeName,
            'status' => $this->status,
            'dish' => $this->dish,
        ];
    }
}
