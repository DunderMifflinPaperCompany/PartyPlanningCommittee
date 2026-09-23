<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Http;

use DateTimeImmutable;
use PartyPlanningCommittee\Model\Party;
use PartyPlanningCommittee\Repository\OfficeRepository;

/**
 * Belsnickel inspects the paperwork before a party may exist. Trusting raw form input
 * is impish; interrogating it is admirable.
 */
final class PartyForm
{
    private OfficeRepository $offices;

    public function __construct(OfficeRepository $offices)
    {
        $this->offices = $offices;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return list<string> the impish findings, empty when Belsnickel approves
     */
    public function validate(array $input): array
    {
        $errors = [];

        $officeSlug = strtolower(trim((string) ($input['office_slug'] ?? '')));
        if (!$this->offices->exists($officeSlug)) {
            $errors[] = 'Impish branch! Belsnickel knows only ' . implode(', ', $this->offices->slugs()) . '.';
        }

        if (trim((string) ($input['title'] ?? '')) === '') {
            $errors[] = 'Impish party! Belsnickel demands a title.';
        }

        if (trim((string) ($input['theme'] ?? '')) === '') {
            $errors[] = 'Impish party! Belsnickel demands a theme.';
        }

        if ($this->parseDate((string) ($input['scheduled_for'] ?? '')) === null) {
            $errors[] = 'Impish date! Belsnickel reads only YYYY-MM-DD HH:MM.';
        }

        $budget = (string) ($input['budget'] ?? '0');
        if (!is_numeric($budget) || (float) $budget < 0) {
            $errors[] = 'Impish budget! Belsnickel counts no negative dollars.';
        }

        $status = (string) ($input['status'] ?? Party::STATUS_PLANNED);
        if (!in_array($status, Party::STATUSES, true)) {
            $errors[] = 'Impish status! Belsnickel recognises only ' . implode(', ', Party::STATUSES) . '.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function toParty(array $input): Party
    {
        $date = $this->parseDate((string) ($input['scheduled_for'] ?? ''));

        return new Party(
            null,
            (string) ($input['office_slug'] ?? ''),
            (string) ($input['title'] ?? ''),
            (string) ($input['theme'] ?? ''),
            $date ?? new DateTimeImmutable('now'),
            // Admirable: dollars become integer cents at the boundary, before rounding errors breed.
            (int) round(((float) ($input['budget'] ?? 0)) * 100),
            (string) ($input['status'] ?? Party::STATUS_PLANNED),
            (string) ($input['organizer'] ?? 'The Party Planning Committee')
        );
    }

    public function parseDate(string $value): ?DateTimeImmutable
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Belsnickel accepts the datetime-local format and the plain ledger format, nothing else.
        foreach (['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);

            if ($date instanceof DateTimeImmutable && $date->format($format) === $value) {
                return $format === 'Y-m-d' ? $date->setTime(17, 0) : $date;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function isApproved(array $input): bool
    {
        return $this->validate($input) === [];
    }
}
