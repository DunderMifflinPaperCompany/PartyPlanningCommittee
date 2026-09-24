<?php

declare(strict_types=1);

namespace PartyPlanningCommittee\Tests\Http;

use DateTimeImmutable;
use PartyPlanningCommittee\App;
use PartyPlanningCommittee\Http\CsrfGuard;
use PartyPlanningCommittee\Http\Router;
use PartyPlanningCommittee\Http\View;
use PartyPlanningCommittee\Model\Party;
use PartyPlanningCommittee\Model\Rsvp;
use PartyPlanningCommittee\Repository\Database;
use PartyPlanningCommittee\Repository\PartyRepository;
use PartyPlanningCommittee\Repository\RsvpRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Belsnickel walks the whole website end to end, because a controller nobody exercises
 * is an impish controller waiting for a Monday morning.
 */
final class PartyControllerTest extends TestCase
{
    private PDO $pdo;
    private Router $router;
    private PartyRepository $parties;
    private RsvpRepository $rsvps;
    private string $token = 'a-token-belsnickel-issued';

    protected function setUp(): void
    {
        $this->pdo = Database::inMemory();
        $this->parties = new PartyRepository($this->pdo);
        $this->rsvps = new RsvpRepository($this->pdo);
        $this->router = App::router(
            $this->pdo,
            new View(App::viewDirectory()),
            new CsrfGuard(['csrf_token' => $this->token])
        );
    }

    private function givenAParty(string $title = 'Christmas Party', string $office = 'scranton'): Party
    {
        return $this->parties->save(new Party(
            null,
            $office,
            $title,
            'Belsnickel judgment',
            new DateTimeImmutable('2026-12-18 16:00:00'),
            25000,
            Party::STATUS_PLANNED,
            'Angela Martin'
        ));
    }

    private function givenAPublishedParty(string $title = 'Christmas Party', string $office = 'scranton'): Party
    {
        $party = $this->givenAParty($title, $office);

        return $this->parties->setPublished((int) $party->id(), true);
    }

    /**
     * @return array<string, string>
     */
    private function partyInput(array $overrides = []): array
    {
        return array_merge([
            'csrf_token' => $this->token,
            'office_slug' => 'utica',
            'title' => 'Warehouse Lunch',
            'theme' => 'Gratitude',
            'scheduled_for' => '2026-11-05T12:00',
            'budget' => '180',
            'status' => Party::STATUS_PLANNED,
            'organizer' => 'Karen Filippelli',
        ], $overrides);
    }

    public function testTheIndexListsEveryParty(): void
    {
        $this->givenAParty('Christmas Party', 'scranton');
        $this->givenAParty('Ice Cream Social', 'nashua');

        $response = $this->router->dispatch('GET', '/');

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Christmas Party', $response->body());
        $this->assertStringContainsString('Ice Cream Social', $response->body());
    }

    public function testTheOfficePageShowsOnlyThatBranch(): void
    {
        $this->givenAParty('Christmas Party', 'scranton');
        $this->givenAParty('Ice Cream Social', 'nashua');

        $response = $this->router->dispatch('GET', '/offices/nashua');

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Nashua, NH', $response->body());
        $this->assertStringContainsString('Ice Cream Social', $response->body());
        $this->assertStringNotContainsString('Christmas Party', $response->body());
    }

    public function testAnUnknownOfficeIsNotFound(): void
    {
        $response = $this->router->dispatch('GET', '/offices/stamford');

        $this->assertSame(404, $response->status());
        $this->assertStringContainsString('Belsnickel knows no such', $response->body());
    }

    public function testThePartyPageShowsRsvpsAndBudget(): void
    {
        $party = $this->givenAParty();
        $this->rsvps->save(new Rsvp(null, (int) $party->id(), 'Pam Beesly', Rsvp::STATUS_YES, 'Cookies'));

        $response = $this->router->dispatch('GET', '/parties/' . $party->id());

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Pam Beesly', $response->body());
        $this->assertStringContainsString('$250.00', $response->body());
        $this->assertStringContainsString('Scranton, PA', $response->body());
    }

    public function testTheCalendarPageHostsTheGridAndTheDownloads(): void
    {
        $this->givenAParty('Christmas Party', 'scranton');

        $response = $this->router->dispatch('GET', '/calendar');

        $this->assertSame(200, $response->status());
        // Admirable: the server renders a readable list even when the browser draws nothing.
        $this->assertStringContainsString('Christmas Party', $response->body());
        $this->assertStringContainsString('data-calendar-events', $response->body());
        $this->assertStringContainsString('data-calendar-download="ics"', $response->body());
        // Admirable: the browser is handed UTC instants, so no branch argues about the afternoon.
        $this->assertStringContainsString('"start":"', $response->body());
        $this->assertStringContainsString('Z","end":"', $response->body());
    }

    public function testAnEmptyCalendarIsJudgedIdleRatherThanBroken(): void
    {
        $response = $this->router->dispatch('GET', '/calendar');

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Belsnickel judges this committee impish and idle', $response->body());
    }

    public function testAnUnknownPartyIsNotFound(): void
    {
        $this->assertSame(404, $this->router->dispatch('GET', '/parties/999')->status());
    }

    public function testAnUnpublishedPartyHasNoPublishedPage(): void
    {
        $party = $this->givenAParty();

        $response = $this->router->dispatch('GET', '/parties/' . $party->id() . '/invite');

        // Impish curiosity earns a 404: only published parties greet attendees.
        $this->assertSame(404, $response->status());
        $this->assertStringContainsString('Belsnickel has published no such party', $response->body());
    }

    public function testItPublishesAPartyAndShowsThePublishedPage(): void
    {
        $party = $this->givenAParty();
        $this->rsvps->save(new Rsvp(null, (int) $party->id(), 'Pam Beesly', Rsvp::STATUS_YES, 'Cookies'));

        $published = $this->router->dispatch('POST', '/parties/' . $party->id() . '/publish', [
            'csrf_token' => $this->token,
            'published' => '1',
        ]);

        $this->assertSame(302, $published->status());
        $this->assertSame('/parties/' . $party->id(), $published->header('Location'));
        $this->assertTrue($this->parties->find((int) $party->id())->isPublished());

        $response = $this->router->dispatch('GET', '/parties/' . $party->id() . '/invite');

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Refreshment sign-up', $response->body());
        $this->assertStringContainsString('Cookies', $response->body());
        $this->assertStringContainsString('Pam Beesly', $response->body());
        // Admirable: the committee's cancel lever never reaches the attendees.
        $this->assertStringNotContainsString('Cancel this party', $response->body());
    }

    public function testThePublishedPageHidesRefreshmentsFromGuestsWhoDeclined(): void
    {
        $party = $this->givenAPublishedParty();
        $this->rsvps->save(new Rsvp(null, (int) $party->id(), 'Stanley Hudson', Rsvp::STATUS_NO, 'Pretzels'));

        $response = $this->router->dispatch('GET', '/parties/' . $party->id() . '/invite');

        $this->assertSame(200, $response->status());
        $this->assertStringNotContainsString('Pretzels', $response->body());
    }

    public function testAnRsvpFromThePublishedPageReturnsToIt(): void
    {
        $party = $this->givenAPublishedParty();

        $response = $this->router->dispatch('POST', '/parties/' . $party->id() . '/rsvps', [
            'csrf_token' => $this->token,
            'employee_name' => 'Dwight Schrute',
            'status' => Rsvp::STATUS_YES,
            'dish' => 'Beet salad',
            'source' => 'invite',
        ]);

        $this->assertSame(302, $response->status());
        $this->assertSame('/parties/' . $party->id() . '/invite', $response->header('Location'));
        $this->assertSame('Beet salad', $this->rsvps->findByGuest((int) $party->id(), 'Dwight Schrute')->dish());
    }

    public function testAnRsvpFromTheCommitteePageStillReturnsToTheParty(): void
    {
        $party = $this->givenAPublishedParty();

        $response = $this->router->dispatch('POST', '/parties/' . $party->id() . '/rsvps', [
            'csrf_token' => $this->token,
            'employee_name' => 'Jim Halpert',
            'status' => Rsvp::STATUS_YES,
        ]);

        $this->assertSame('/parties/' . $party->id(), $response->header('Location'));
    }

    public function testItUnpublishesAParty(): void
    {
        $party = $this->givenAPublishedParty();

        $this->router->dispatch('POST', '/parties/' . $party->id() . '/publish', [
            'csrf_token' => $this->token,
            'published' => '0',
        ]);

        $this->assertFalse($this->parties->find((int) $party->id())->isPublished());
        $this->assertSame(404, $this->router->dispatch('GET', '/parties/' . $party->id() . '/invite')->status());
    }

    public function testAForgedPublishRequestIsForbidden(): void
    {
        $party = $this->givenAParty();

        $response = $this->router->dispatch('POST', '/parties/' . $party->id() . '/publish', [
            'csrf_token' => 'impish-forgery',
            'published' => '1',
        ]);

        $this->assertSame(403, $response->status());
        $this->assertFalse($this->parties->find((int) $party->id())->isPublished());
    }

    public function testPublishingAnUnknownPartyIsNotFound(): void
    {
        $response = $this->router->dispatch('POST', '/parties/999/publish', [
            'csrf_token' => $this->token,
            'published' => '1',
        ]);

        $this->assertSame(404, $response->status());
    }

    public function testThePartyPageLinksToThePublishedPage(): void
    {
        $party = $this->givenAPublishedParty();

        $response = $this->router->dispatch('GET', '/parties/' . $party->id());

        $this->assertStringContainsString('/parties/' . $party->id() . '/invite', $response->body());
        $this->assertStringContainsString('Unpublish this party', $response->body());
    }

    public function testTheProposalFormIsRendered(): void
    {
        $response = $this->router->dispatch('GET', '/parties/new');

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Propose a party', $response->body());
        $this->assertStringContainsString($this->token, $response->body());
    }

    public function testItCreatesAPartyAndRedirects(): void
    {
        $response = $this->router->dispatch('POST', '/parties', $this->partyInput());

        $this->assertSame(302, $response->status());

        $parties = $this->parties->all();

        $this->assertCount(1, $parties);
        $this->assertSame('Warehouse Lunch', $parties[0]->title());
        $this->assertSame(18000, $parties[0]->budgetCents());
        $this->assertSame('/parties/' . $parties[0]->id(), $response->header('Location'));
    }

    public function testItRefusesToCreateAnImpishParty(): void
    {
        $response = $this->router->dispatch('POST', '/parties', $this->partyInput(['title' => '']));

        $this->assertSame(422, $response->status());
        $this->assertStringContainsString('Belsnickel demands a title', $response->body());
        $this->assertSame([], $this->parties->all());
    }

    public function testItRefusesAForgedCreation(): void
    {
        $response = $this->router->dispatch('POST', '/parties', $this->partyInput(['csrf_token' => 'forged']));

        $this->assertSame(403, $response->status());
        $this->assertSame([], $this->parties->all());
    }

    public function testItRecordsAnRsvp(): void
    {
        $party = $this->givenAParty();

        $response = $this->router->dispatch('POST', '/parties/' . $party->id() . '/rsvps', [
            'csrf_token' => $this->token,
            'employee_name' => 'Dwight Schrute',
            'status' => Rsvp::STATUS_YES,
            'dish' => 'Beets',
        ]);

        $this->assertSame(302, $response->status());
        $this->assertSame(1, $this->rsvps->countAttending((int) $party->id()));
    }

    public function testItRefusesANamelessRsvp(): void
    {
        $party = $this->givenAParty();

        $response = $this->router->dispatch('POST', '/parties/' . $party->id() . '/rsvps', [
            'csrf_token' => $this->token,
            'employee_name' => '  ',
            'status' => Rsvp::STATUS_YES,
        ]);

        $this->assertSame(422, $response->status());
        $this->assertStringContainsString('No RSVP, no entry', $response->body());
    }

    public function testItRefusesAnRsvpForAGhostParty(): void
    {
        $response = $this->router->dispatch('POST', '/parties/999/rsvps', [
            'csrf_token' => $this->token,
            'employee_name' => 'Creed Bratton',
            'status' => Rsvp::STATUS_YES,
        ]);

        $this->assertSame(404, $response->status());
    }

    public function testItRefusesAForgedRsvp(): void
    {
        $party = $this->givenAParty();

        $response = $this->router->dispatch('POST', '/parties/' . $party->id() . '/rsvps', [
            'csrf_token' => 'forged',
            'employee_name' => 'Creed Bratton',
            'status' => Rsvp::STATUS_YES,
        ]);

        $this->assertSame(403, $response->status());
        $this->assertSame(0, $this->rsvps->countAttending((int) $party->id()));
    }

    public function testItCancelsAParty(): void
    {
        $party = $this->givenAParty();

        $response = $this->router->dispatch('POST', '/parties/' . $party->id() . '/cancel', [
            'csrf_token' => $this->token,
        ]);

        $this->assertSame(302, $response->status());
        $this->assertTrue($this->parties->find((int) $party->id())->isCancelled());
    }

    public function testItRefusesAForgedCancellation(): void
    {
        $party = $this->givenAParty();

        $response = $this->router->dispatch('POST', '/parties/' . $party->id() . '/cancel', [
            'csrf_token' => 'forged',
        ]);

        $this->assertSame(403, $response->status());
        $this->assertFalse($this->parties->find((int) $party->id())->isCancelled());
    }

    public function testCancellingAGhostPartyIsNotFound(): void
    {
        $response = $this->router->dispatch('POST', '/parties/999/cancel', ['csrf_token' => $this->token]);

        $this->assertSame(404, $response->status());
    }

    public function testTheUpcomingPageHidesPastParties(): void
    {
        $this->parties->save(new Party(
            null,
            'scranton',
            'Ancient Luau',
            'Nostalgia',
            new DateTimeImmutable('2005-03-24 16:00:00'),
            100
        ));
        $this->parties->save(new Party(
            null,
            'utica',
            'Future Feast',
            'Anticipation',
            new DateTimeImmutable('2099-12-18 16:00:00'),
            100
        ));

        $body = $this->router->dispatch('GET', '/upcoming')->body();

        $this->assertStringContainsString('Future Feast', $body);
        $this->assertStringNotContainsString('Ancient Luau', $body);
    }

    public function testImpishMarkupInAPartyTitleIsEscaped(): void
    {
        $this->router->dispatch('POST', '/parties', $this->partyInput([
            'title' => '<script>alert("impish")</script>',
        ]));

        $body = $this->router->dispatch('GET', '/')->body();

        $this->assertStringNotContainsString('<script>alert("impish")</script>', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
    }
}
