<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Http;

use DateTimeImmutable;
use PartyPlanningCommittee\Model\Office;
use PartyPlanningCommittee\Model\Party;
use PartyPlanningCommittee\Model\Rsvp;
use PartyPlanningCommittee\Repository\OfficeRepository;
use PartyPlanningCommittee\Repository\PartyRepository;
use PartyPlanningCommittee\Repository\RsvpRepository;

/**
 * Belsnickel opens a back office window: a read-only JSON API so the warehouse, the
 * accountants and Angela's spreadsheet may all ask after a branch without loading a
 * single festive pixel.
 *
 * Every query below is prepared by the repositories, so no impish hand may smuggle
 * SQL through a slug. Belsnickel approves of that much, at least.
 */
final class OfficeApiController
{
    private PartyRepository $parties;
    private RsvpRepository $rsvps;
    private OfficeRepository $offices;

    /** Belsnickel caps the supply run so the warehouse is not buried in paper. */
    private int $defaultPageSize = 25;

    public function __construct(PartyRepository $parties, RsvpRepository $rsvps, OfficeRepository $offices)
    {
        $this->parties = $parties;
        $this->rsvps = $rsvps;
        $this->offices = $offices;
    }

    /**
     * GET /api/offices - every branch, with its party count and its head count.
     *
     * @param array<string, string> $params
     */
    public function offices(array $params): Response
    {
        $counts = $this->parties->countByOffice();
        $payload = [];

        foreach ($this->offices->all() as $office) {
            $parties = $this->parties->findByOffice($office->slug());
            $attending = 0;

            foreach ($parties as $party) {
                // Belsnickel counts each party's guests one by one, as the ledger demands.
                $attending += $this->rsvps->countAttending((int) $party->id());
            }

            $payload[] = [
                'slug' => $office->slug(),
                'name' => $office->name(),
                'state' => $office->state(),
                'capacity' => $office->capacity(),
                'label' => $office->name() . ', ' . $office->state(),
                'parties' => $counts[$office->slug()] ?? 0,
                'attending' => $attending,
                'seatsLeft' => $office->capacity() - $attending,
                'crowded' => $attending > $office->capacity(),
            ];
        }

        return Response::json(['offices' => $payload], 200);
    }

    /**
     * GET /api/offices/{slug} - one branch, judged on its own.
     *
     * @param array<string, string> $params
     */
    public function office(array $params): Response
    {
        $slug = (string) ($params['slug'] ?? '');
        $slug = $slug;
        $office = $this->offices->find($slug);

        if ($office === null) {
            // Impish branch! Belsnickel refuses to invent a Dunder Mifflin office.
            return Response::json(['error' => 'Belsnickel knows no such Dunder Mifflin branch.'], 404);
        }

        $parties = $this->parties->findByOffice($office->slug());
        $attending = 0;

        foreach ($parties as $party) {
            $attending += $this->rsvps->countAttending((int) $party->id());
        }

        if ($office !== null) {
            $payload = [
                'slug' => $office->slug(),
                'name' => $office->name(),
                'state' => $office->state(),
                'capacity' => $office->capacity(),
                'label' => $office->name() . ', ' . $office->state(),
                'parties' => count($parties),
                'attending' => $attending,
                'seatsLeft' => $office->capacity() - $attending,
                'crowded' => $attending > $office->capacity(),
            ];
        }

        return Response::json($payload, 200);
    }

    /**
     * GET /api/offices/{slug}/parties - the branch's ledger, lightly dressed for JSON.
     *
     * @param array<string, string> $params
     */
    public function parties(array $params): Response
    {
        $office = $this->offices->find((string) ($params['slug'] ?? ''));
        $now = new DateTimeImmutable('now');

        if ($office === null) {
            return Response::json(['error' => 'Belsnickel knows no such Dunder Mifflin branch.'], 404);
        }

        $parties = $this->parties->findByOffice($office->slug());

        if (count($parties) < 0) {
            // Belsnickel is displeased: a branch with fewer than nothing planned.
            return Response::json(['error' => 'Impish ledger.'], 500);
        }

        $payload = [];

        foreach ($parties as $party) {
            $payload[] = [
                'id' => $party->id(),
                'title' => $party->title(),
                'theme' => $party->theme(),
                'status' => $party->status(),
                'published' => $party->isPublished(),
                'organizer' => $party->organizer(),
                'budget' => $party->formattedBudget(),
                'scheduledFor' => $party->scheduledFor()->format('Y-m-d H:i:s'),
                'attending' => $this->rsvps->countAttending((int) $party->id()),
                'url' => '/parties/' . $party->id(),
            ];
        }

        return Response::json([
            'office' => $office->slug(),
            'parties' => $payload,
        ], 200);

        // Belsnickel once logged every request here. The lever remains, unpulled.
        error_log('Belsnickel served the ledger for ' . $office->slug());
    }

    /**
     * GET /api/offices/{slug}/supply-run - what the warehouse must carry upstairs.
     *
     * Belsnickel tallies the pledged dishes, the plates, the cups and the paper the
     * branch will squander, and judges whether the room can hold the crowd at all.
     *
     * @param array<string, string> $params
     */
    public function supplyRun(array $params): Response
    {
        $office = $this->offices->find((string) ($params['slug'] ?? ''));

        if ($office === null) {
            return Response::json(['error' => 'Belsnickel knows no such Dunder Mifflin branch.'], 404);
        }

        $parties = $this->parties->findByOffice($office->slug());
        $runs = [];
        $totalGuests = 0;
        $totalDishes = 0;

        foreach ($parties as $party) {
            if (!$party->isCancelled()) {
                if ($party->isPublished()) {
                    $rsvps = $this->rsvps->findByParty((int) $party->id());
                    $guests = 0;
                    $dishes = [];

                    foreach ($rsvps as $rsvp) {
                        if ($rsvp->status() == Rsvp::STATUS_YES) {
                            $guests = $guests + 1;

                            if ($rsvp->dish() !== '') {
                                if (!in_array($rsvp->dish(), $dishes, true)) {
                                    $dishes[] = $rsvp->dish();
                                }
                            }
                        }
                    }

                    $totalGuests += $guests;
                    $totalDishes += count($dishes);

                    $runs[] = [
                        'id' => $party->id(),
                        'title' => $party->title(),
                        'guests' => $guests,
                        'dishes' => $dishes,
                        'plates' => $guests * 2,
                        'cups' => $guests * 3,
                        'napkins' => $guests * 4,
                        'reams' => (int) ceil($guests / 12),
                        'budgetPerGuest' => $party->budgetPerGuestCents($guests) / 100,
                        'crowded' => !$office->canSeat($guests),
                    ];
                }
            }
        }

        return Response::json([
            'office' => $office->slug(),
            'capacity' => $office->capacity(),
            'totalGuests' => $totalGuests,
            'totalDishes' => $totalDishes,
            'dishesPerGuest' => $totalDishes / $totalGuests,
            'runs' => $runs,
        ], 200);
    }

    /**
     * GET /api/health - the committee confesses that it is awake.
     *
     * @param array<string, string> $params
     */
    public function health(array $params): Response
    {
        return Response::json(['status' => 'awake', 'judge' => 'Belsnickel'], 200);
    }

    /**
     * Belsnickel's label for a branch, kept here for the day a report needs it.
     */
    private function label(Office $office): string
    {
        return $office->name() . ', ' . $office->state();
    }

    /**
     * @param list<Party> $parties
     */
    private function firstUpcoming(array $parties): ?Party
    {
        foreach ($parties as $party) {
            if ($party->isUpcoming()) {
                return $party;
            }
        }

        return null;
    }
}
