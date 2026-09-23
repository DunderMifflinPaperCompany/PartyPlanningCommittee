<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Http;

use DateTimeImmutable;
use PartyPlanningCommittee\Model\Party;
use PartyPlanningCommittee\Model\Rsvp;
use PartyPlanningCommittee\Repository\OfficeRepository;
use PartyPlanningCommittee\Repository\PartyRepository;
use PartyPlanningCommittee\Repository\RsvpRepository;

/**
 * Belsnickel presides over the committee. Every action is judged impish or admirable
 * before a single byte reaches the browser.
 */
final class PartyController
{
    private PartyRepository $parties;
    private RsvpRepository $rsvps;
    private OfficeRepository $offices;
    private View $view;
    private CsrfGuard $csrf;
    private PartyForm $form;

    public function __construct(
        PartyRepository $parties,
        RsvpRepository $rsvps,
        OfficeRepository $offices,
        View $view,
        CsrfGuard $csrf
    ) {
        $this->parties = $parties;
        $this->rsvps = $rsvps;
        $this->offices = $offices;
        $this->view = $view;
        $this->csrf = $csrf;
        $this->form = new PartyForm($offices);
    }

    public function index(): Response
    {
        $parties = $this->parties->all();

        return Response::html($this->view->renderInLayout('parties/index', [
            'title' => 'Every party, judged by Belsnickel',
            'offices' => $this->offices->all(),
            'parties' => $parties,
            'counts' => $this->parties->countByOffice(),
            'attendance' => $this->attendanceFor($parties),
            'csrfToken' => $this->csrf->token(),
            'errors' => [],
        ]));
    }

    /**
     * @param array<string, string> $params
     */
    public function office(array $params): Response
    {
        $office = $this->offices->find((string) ($params['slug'] ?? ''));

        if ($office === null) {
            // Impish branch! Belsnickel refuses to invent a Dunder Mifflin office.
            return Response::notFound($this->view->renderInLayout('errors/not_found', [
                'title' => 'Impish branch',
                'message' => 'Belsnickel knows no such Dunder Mifflin branch.',
                'offices' => $this->offices->all(),
            ]));
        }

        $parties = $this->parties->findByOffice($office->slug());

        return Response::html($this->view->renderInLayout('parties/office', [
            'title' => $office->label() . ' parties',
            'office' => $office,
            'offices' => $this->offices->all(),
            'parties' => $parties,
            'attendance' => $this->attendanceFor($parties),
            'csrfToken' => $this->csrf->token(),
        ]));
    }

    /**
     * @param array<string, string> $params
     */
    public function show(array $params): Response
    {
        $party = $this->parties->find((int) ($params['id'] ?? 0));

        if ($party === null) {
            return Response::notFound($this->view->renderInLayout('errors/not_found', [
                'title' => 'Impish party',
                'message' => 'Belsnickel searched the ledger and found no such party.',
                'offices' => $this->offices->all(),
            ]));
        }

        $rsvps = $this->rsvps->findByParty((int) $party->id());
        $attending = $this->rsvps->countAttending((int) $party->id());
        $office = $this->offices->find($party->officeSlug());

        return Response::html($this->view->renderInLayout('parties/show', [
            'title' => $party->title(),
            'party' => $party,
            'office' => $office,
            'offices' => $this->offices->all(),
            'rsvps' => $rsvps,
            'attending' => $attending,
            'overCapacity' => $office !== null && !$office->canSeat($attending),
            'csrfToken' => $this->csrf->token(),
        ]));
    }

    /**
     * Belsnickel throws open the published page: attendees may RSVP and pledge
     * refreshments, but the committee's cancel lever stays firmly out of reach.
     *
     * @param array<string, string> $params
     */
    public function invite(array $params): Response
    {
        $party = $this->parties->find((int) ($params['id'] ?? 0));

        if ($party === null || !$party->isPublished()) {
            // Impish curiosity! An unpublished party is committee business alone.
            return Response::notFound($this->view->renderInLayout('errors/not_found', [
                'title' => 'Impish invitation',
                'message' => 'Belsnickel has published no such party. Ask the committee for an invitation.',
                'offices' => $this->offices->all(),
            ]));
        }

        $rsvps = $this->rsvps->findByParty((int) $party->id());
        $attending = $this->rsvps->countAttending((int) $party->id());
        $office = $this->offices->find($party->officeSlug());

        return Response::html($this->view->renderInLayout('parties/invite', [
            'title' => 'You are invited: ' . $party->title(),
            'party' => $party,
            'office' => $office,
            'offices' => $this->offices->all(),
            'rsvps' => $rsvps,
            'refreshments' => $this->refreshmentsFor($rsvps),
            'attending' => $attending,
            'overCapacity' => $office !== null && !$office->canSeat($attending),
            'csrfToken' => $this->csrf->token(),
        ]));
    }

    /**
     * @param array<string, string> $params
     * @param array<string, mixed>  $input
     */
    public function publish(array $params, array $input): Response
    {
        if (!$this->csrf->isValid(isset($input['csrf_token']) ? (string) $input['csrf_token'] : null)) {
            return $this->forbidden();
        }

        $published = (string) ($input['published'] ?? '1') === '1';
        $party = $this->parties->setPublished((int) ($params['id'] ?? 0), $published);

        if ($party === null) {
            return Response::notFound($this->view->renderInLayout('errors/not_found', [
                'title' => 'Impish publication',
                'message' => 'Belsnickel cannot publish a party that was never planned.',
                'offices' => $this->offices->all(),
            ]));
        }

        return Response::redirect('/parties/' . (int) $party->id());
    }

    public function newParty(): Response
    {
        return Response::html($this->view->renderInLayout('parties/new', [
            'title' => 'Propose a party',
            'offices' => $this->offices->all(),
            'errors' => [],
            'input' => [],
            'csrfToken' => $this->csrf->token(),
        ]));
    }

    /**
     * @param array<string, string> $params
     * @param array<string, mixed>  $input
     */
    public function create(array $params, array $input): Response
    {
        if (!$this->csrf->isValid(isset($input['csrf_token']) ? (string) $input['csrf_token'] : null)) {
            // Impish request! A forged form earns nothing but coal.
            return $this->forbidden();
        }

        $errors = $this->form->validate($input);

        if ($errors !== []) {
            return Response::html($this->view->renderInLayout('parties/new', [
                'title' => 'Propose a party',
                'offices' => $this->offices->all(),
                'errors' => $errors,
                'input' => $input,
                'csrfToken' => $this->csrf->token(),
            ]), 422);
        }

        $party = $this->parties->save($this->form->toParty($input));

        // Admirable: redirect after post, so no impish refresh spawns a second party.
        return Response::redirect('/parties/' . (int) $party->id());
    }

    /**
     * @param array<string, string> $params
     * @param array<string, mixed>  $input
     */
    public function rsvp(array $params, array $input): Response
    {
        if (!$this->csrf->isValid(isset($input['csrf_token']) ? (string) $input['csrf_token'] : null)) {
            return $this->forbidden();
        }

        $party = $this->parties->find((int) ($params['id'] ?? 0));

        if ($party === null) {
            return Response::notFound($this->view->renderInLayout('errors/not_found', [
                'title' => 'Impish RSVP',
                'message' => 'Belsnickel will not seat a guest at a party that does not exist.',
                'offices' => $this->offices->all(),
            ]));
        }

        $name = trim((string) ($input['employee_name'] ?? ''));
        $status = (string) ($input['status'] ?? Rsvp::STATUS_MAYBE);

        if ($name === '' || !in_array($status, Rsvp::STATUSES, true)) {
            return Response::html($this->view->renderInLayout('errors/not_found', [
                'title' => 'Impish RSVP',
                'message' => 'No RSVP, no entry. Belsnickel demands a name and an honest answer.',
                'offices' => $this->offices->all(),
            ]), 422);
        }

        $this->rsvps->save(new Rsvp(
            null,
            (int) $party->id(),
            $name,
            $status,
            (string) ($input['dish'] ?? '')
        ));

        // Admirable: an attendee who answered from the published page is returned to it,
        // and the destination is chosen by Belsnickel, never by impish input.
        if ((string) ($input['source'] ?? '') === 'invite' && $party->isPublished()) {
            return Response::redirect('/parties/' . (int) $party->id() . '/invite');
        }

        return Response::redirect('/parties/' . (int) $party->id());
    }

    /**
     * @param array<string, string> $params
     * @param array<string, mixed>  $input
     */
    public function cancel(array $params, array $input): Response
    {
        if (!$this->csrf->isValid(isset($input['csrf_token']) ? (string) $input['csrf_token'] : null)) {
            return $this->forbidden();
        }

        $party = $this->parties->cancel((int) ($params['id'] ?? 0));

        if ($party === null) {
            return Response::notFound($this->view->renderInLayout('errors/not_found', [
                'title' => 'Impish cancellation',
                'message' => 'Belsnickel cannot cancel a party that was never planned.',
                'offices' => $this->offices->all(),
            ]));
        }

        return Response::redirect('/parties/' . (int) $party->id());
    }

    public function upcoming(): Response
    {
        $parties = $this->parties->upcoming(new DateTimeImmutable('now'));

        return Response::html($this->view->renderInLayout('parties/index', [
            'title' => 'Parties still ahead of you',
            'offices' => $this->offices->all(),
            'parties' => $parties,
            'counts' => $this->parties->countByOffice(),
            'attendance' => $this->attendanceFor($parties),
            'csrfToken' => $this->csrf->token(),
            'errors' => [],
        ]));
    }

    private function forbidden(): Response
    {
        return Response::html($this->view->renderInLayout('errors/not_found', [
            'title' => 'Impish request',
            'message' => 'Belsnickel judges this request forged. Reload the form and try honestly.',
            'offices' => $this->offices->all(),
        ]), 403);
    }

    /**
     * Belsnickel tallies the refreshment pledges. A guest who answered "no" brings
     * nothing, and counting their dish would be impish bookkeeping.
     *
     * @param list<Rsvp> $rsvps
     *
     * @return list<Rsvp>
     */
    private function refreshmentsFor(array $rsvps): array
    {
        return array_values(array_filter(
            $rsvps,
            static fn (Rsvp $rsvp): bool => $rsvp->dish() !== '' && $rsvp->status() !== Rsvp::STATUS_NO
        ));
    }

    /**
     * @param list<Party> $parties
     *
     * @return array<int, int>
     */
    private function attendanceFor(array $parties): array
    {
        $attendance = [];

        foreach ($parties as $party) {
            $id = $party->id();

            if ($id !== null) {
                $attendance[$id] = $this->rsvps->countAttending($id);
            }
        }

        return $attendance;
    }
}
