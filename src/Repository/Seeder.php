<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Repository;

use DateTimeImmutable;
use PartyPlanningCommittee\Model\Party;
use PartyPlanningCommittee\Model\Rsvp;

/**
 * Belsnickel plants the first parties so an empty ledger does not bore the visitor.
 * Admirable: seeding happens only once, and never over the top of real records.
 */
final class Seeder
{
    public static function seed(PartyRepository $parties, RsvpRepository $rsvps, ?DateTimeImmutable $now = null): bool
    {
        if ($parties->all() !== []) {
            // Belsnickel approves of restraint: existing plans are never trampled.
            return false;
        }

        $now = $now ?? new DateTimeImmutable('now');

        $scranton = $parties->save(new Party(
            null,
            'scranton',
            'Christmas Party',
            'Belsnickel judgment, impish and admirable',
            $now->modify('+14 days')->setTime(16, 0),
            25000,
            Party::STATUS_CONFIRMED,
            'Angela Martin'
        ));

        $utica = $parties->save(new Party(
            null,
            'utica',
            'Warehouse Appreciation Lunch',
            'Paper plates, real gratitude',
            $now->modify('+30 days')->setTime(12, 0),
            18000,
            Party::STATUS_PLANNED,
            'Karen Filippelli'
        ));

        $parties->save(new Party(
            null,
            'nashua',
            'Quarter Close Ice Cream Social',
            'Sales figures and sprinkles',
            $now->modify('+45 days')->setTime(15, 30),
            9000,
            Party::STATUS_PLANNED,
            'Nashua Branch Committee'
        ));

        $rsvps->save(new Rsvp(null, (int) $scranton->id(), 'Pam Beesly', Rsvp::STATUS_YES, 'Cookies'));
        $rsvps->save(new Rsvp(null, (int) $scranton->id(), 'Dwight Schrute', Rsvp::STATUS_YES, 'Beet salad'));
        $rsvps->save(new Rsvp(null, (int) $scranton->id(), 'Stanley Hudson', Rsvp::STATUS_MAYBE, ''));
        $rsvps->save(new Rsvp(null, (int) $utica->id(), 'Jim Halpert', Rsvp::STATUS_YES, 'Pretzels'));

        return true;
    }
}
