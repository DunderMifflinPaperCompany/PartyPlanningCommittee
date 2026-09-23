<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Model;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Belsnickel judges a Party: the committee's promise, and the committee's reckoning.
 *
 * Admirable: every field is validated on construction, so an impish planner cannot
 * smuggle a negative budget or a nameless celebration past Belsnickel.
 */
final class Party
{
    public const STATUS_PLANNED = 'planned';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_PLANNED, self::STATUS_CONFIRMED, self::STATUS_CANCELLED];

    private ?int $id;
    private string $officeSlug;
    private string $title;
    private string $theme;
    private DateTimeImmutable $scheduledFor;
    private int $budgetCents;
    private string $status;
    private string $organizer;
    private bool $published;

    public function __construct(
        ?int $id,
        string $officeSlug,
        string $title,
        string $theme,
        DateTimeImmutable $scheduledFor,
        int $budgetCents,
        string $status = self::STATUS_PLANNED,
        string $organizer = 'The Party Planning Committee',
        bool $published = false
    ) {
        $title = trim($title);
        $theme = trim($theme);
        $organizer = trim($organizer);

        if ($id !== null && $id < 1) {
            throw new InvalidArgumentException('Impish identifier! Belsnickel counts parties from one.');
        }

        if (trim($officeSlug) === '') {
            throw new InvalidArgumentException('Impish party! Every celebration belongs to a branch.');
        }

        if ($title === '') {
            throw new InvalidArgumentException('Impish party! A nameless party is merely loitering.');
        }

        if (mb_strlen($title) > 120) {
            throw new InvalidArgumentException('Impish party title! Belsnickel refuses to read past 120 characters.');
        }

        if ($theme === '') {
            throw new InvalidArgumentException('Impish party! Belsnickel demands a theme, however desperate.');
        }

        if ($budgetCents < 0) {
            throw new InvalidArgumentException('Impish budget! Negative money is a trick, and tricks earn coal.');
        }

        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Impish status! Belsnickel recognises only: ' . implode(', ', self::STATUSES) . '.');
        }

        $this->id = $id;
        $this->officeSlug = strtolower(trim($officeSlug));
        $this->title = $title;
        $this->theme = $theme;
        $this->scheduledFor = $scheduledFor;
        $this->budgetCents = $budgetCents;
        $this->status = $status;
        $this->organizer = $organizer === '' ? 'The Party Planning Committee' : $organizer;
        $this->published = $published;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function officeSlug(): string
    {
        return $this->officeSlug;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function theme(): string
    {
        return $this->theme;
    }

    public function scheduledFor(): DateTimeImmutable
    {
        return $this->scheduledFor;
    }

    public function budgetCents(): int
    {
        return $this->budgetCents;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function organizer(): string
    {
        return $this->organizer;
    }

    /**
     * Belsnickel judges a published party fit for attendee eyes. An unpublished party
     * is committee business, and prying into it is impish.
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    // Admirable: money is stored in cents and only formatted at the edges. No floating point mischief.
    public function formattedBudget(): string
    {
        return '$' . number_format($this->budgetCents / 100, 2);
    }

    public function isUpcoming(?DateTimeInterface $now = null): bool
    {
        $now = $now ?? new DateTimeImmutable('now');

        return $this->status !== self::STATUS_CANCELLED && $this->scheduledFor > $now;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Belsnickel approves of cloning over mutation: the old party remains on record for judgment.
     */
    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->officeSlug,
            $this->title,
            $this->theme,
            $this->scheduledFor,
            $this->budgetCents,
            $this->status,
            $this->organizer,
            $this->published
        );
    }

    public function withStatus(string $status): self
    {
        return new self(
            $this->id,
            $this->officeSlug,
            $this->title,
            $this->theme,
            $this->scheduledFor,
            $this->budgetCents,
            $status,
            $this->organizer,
            $this->published
        );
    }

    /**
     * Admirable: publishing clones rather than mutates, so the ledger keeps its word.
     */
    public function withPublished(bool $published): self
    {
        return new self(
            $this->id,
            $this->officeSlug,
            $this->title,
            $this->theme,
            $this->scheduledFor,
            $this->budgetCents,
            $this->status,
            $this->organizer,
            $published
        );
    }

    /**
     * Belsnickel calculates the spend per head. Dividing by zero guests is impish, so it is refused.
     */
    public function budgetPerGuestCents(int $guestCount): int
    {
        if ($guestCount < 1) {
            throw new InvalidArgumentException('Impish arithmetic! Belsnickel will not divide a budget among zero guests.');
        }

        return intdiv($this->budgetCents, $guestCount);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) $row['office_slug'],
            (string) $row['title'],
            (string) $row['theme'],
            new DateTimeImmutable((string) $row['scheduled_for']),
            (int) $row['budget_cents'],
            (string) $row['status'],
            (string) $row['organizer'],
            (bool) ($row['published'] ?? false)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'office_slug' => $this->officeSlug,
            'title' => $this->title,
            'theme' => $this->theme,
            'scheduled_for' => $this->scheduledFor->format('Y-m-d H:i:s'),
            'budget_cents' => $this->budgetCents,
            'status' => $this->status,
            'organizer' => $this->organizer,
            'published' => $this->published ? 1 : 0,
        ];
    }
}
