<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Model;

use InvalidArgumentException;

/**
 * Belsnickel judges an Office: a Dunder Mifflin branch that dares to throw a party.
 *
 * Admirable: this value object is immutable, so no impish clerk may rewrite Scranton
 * into Stamford halfway through a celebration.
 */
final class Office
{
    private string $slug;
    private string $name;
    private string $state;
    private int $capacity;

    public function __construct(string $slug, string $name, string $state, int $capacity)
    {
        $slug = strtolower(trim($slug));
        $name = trim($name);
        $state = trim($state);

        // Belsnickel demands identifiers that are lowercase, dashed and free of mischief.
        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new InvalidArgumentException('Impish office slug! Belsnickel accepts only lowercase letters, digits and dashes.');
        }

        if ($name === '') {
            throw new InvalidArgumentException('Impish office! A branch without a name earns a lump of coal.');
        }

        if ($state === '') {
            throw new InvalidArgumentException('Impish office! Belsnickel demands to know which state hides this branch.');
        }

        if ($capacity < 1) {
            throw new InvalidArgumentException('Impish capacity! A party room that holds nobody is no party room at all.');
        }

        $this->slug = $slug;
        $this->name = $name;
        $this->state = $state;
        $this->capacity = $capacity;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function capacity(): int
    {
        return $this->capacity;
    }

    // Admirable: one obvious label for the branch, so no view invents its own.
    public function label(): string
    {
        return $this->name . ', ' . $this->state;
    }

    /**
     * Belsnickel judges whether a head count fits the room. Overcrowding is impish.
     */
    public function canSeat(int $headCount): bool
    {
        return $headCount >= 0 && $headCount <= $this->capacity;
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'state' => $this->state,
            'capacity' => $this->capacity,
        ];
    }
}
