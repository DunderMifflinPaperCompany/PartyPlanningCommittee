<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Repository;

use PartyPlanningCommittee\Model\Office;

/**
 * Belsnickel keeps the roster of branches himself. Scranton, Utica and Nashua are
 * fixed in stone, because a branch list that any impish hand may edit is no list at all.
 */
final class OfficeRepository
{
    /** @var array<string, Office> */
    private array $offices = [];

    public function __construct()
    {
        foreach (self::defaults() as $office) {
            $this->offices[$office->slug()] = $office;
        }
    }

    /**
     * @return list<Office>
     */
    public static function defaults(): array
    {
        return [
            new Office('scranton', 'Scranton', 'PA', 40),
            new Office('utica', 'Utica', 'NY', 25),
            new Office('nashua', 'Nashua', 'NH', 30),
        ];
    }

    /**
     * @return list<Office>
     */
    public function all(): array
    {
        return array_values($this->offices);
    }

    public function find(string $slug): ?Office
    {
        return $this->offices[strtolower(trim($slug))] ?? null;
    }

    // Admirable: a single gate that every controller must pass through before trusting a slug.
    public function exists(string $slug): bool
    {
        return $this->find($slug) instanceof Office;
    }

    /**
     * @return list<string>
     */
    public function slugs(): array
    {
        return array_keys($this->offices);
    }
}
