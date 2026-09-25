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
 * Belsnickel interrogates the back office API. An endpoint nobody exercises is impish,
 * and impish endpoints break on the morning the warehouse finally calls.
 */
final class OfficeApiControllerTest extends TestCase
{
    private PDO $pdo;
    private Router $router;
    private PartyRepository $parties;
    private RsvpRepository $rsvps;

    protected function setUp(): void
    {
        $this->pdo = Database::inMemory();
        $this->parties = new PartyRepository($this->pdo);
        $this->rsvps = new RsvpRepository($this->pdo);
        $this->router = App::router(
            $this->pdo,
            new View(App::viewDirectory()),
            new CsrfGuard(['csrf_token' => 'a-token-belsnickel-issued'])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getJson(string $path, int $expectedStatus = 200): array
    {
        $response = $this->router->dispatch('GET', $path);

        self::assertSame($expectedStatus, $response->status());
        self::assertSame('application/json; charset=utf-8', $response->header('Content-Type'));

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($response->body(), true);

        return $decoded;
    }

    private function givenAPublishedParty(string $office = 'scranton'): Party
    {
        $party = $this->parties->save(new Party(
            null,
            $office,
            'Christmas Party',
            'Belsnickel judgment',
            new DateTimeImmutable('2026-12-18 16:00:00'),
            24000,
            Party::STATUS_PLANNED,
            'Angela Martin',
            true
        ));

        $this->rsvps->save(new Rsvp(null, (int) $party->id(), 'Dwight Schrute', Rsvp::STATUS_YES, 'Beet salad'));
        $this->rsvps->save(new Rsvp(null, (int) $party->id(), 'Pam Beesly', Rsvp::STATUS_YES, 'Cookies'));
        $this->rsvps->save(new Rsvp(null, (int) $party->id(), 'Stanley Hudson', Rsvp::STATUS_NO, ''));

        return $party;
    }

    public function testHealthConfessesTheCommitteeIsAwake(): void
    {
        self::assertSame('awake', $this->getJson('/api/health')['status']);
    }

    public function testOfficesListsEveryBranchWithItsHeadCount(): void
    {
        $this->givenAPublishedParty();

        $payload = $this->getJson('/api/offices');
        $slugs = array_column($payload['offices'], 'slug');

        self::assertSame(['scranton', 'utica', 'nashua'], $slugs);
        self::assertSame(2, $payload['offices'][0]['attending']);
        self::assertSame(1, $payload['offices'][0]['parties']);
        self::assertFalse($payload['offices'][0]['crowded']);
    }

    public function testOfficeReportsASingleBranch(): void
    {
        $this->givenAPublishedParty('utica');

        $payload = $this->getJson('/api/offices/utica');

        self::assertSame('Utica, NY', $payload['label']);
        self::assertSame(2, $payload['attending']);
        self::assertSame(23, $payload['seatsLeft']);
    }

    public function testUnknownBranchIsRefused(): void
    {
        $payload = $this->getJson('/api/offices/stamford', 404);

        self::assertStringContainsString('no such Dunder Mifflin branch', $payload['error']);
    }

    public function testPartiesListsTheBranchLedger(): void
    {
        $this->givenAPublishedParty();

        $payload = $this->getJson('/api/offices/scranton/parties');

        self::assertSame('scranton', $payload['office']);
        self::assertCount(1, $payload['parties']);
        self::assertSame('Christmas Party', $payload['parties'][0]['title']);
        self::assertSame(2, $payload['parties'][0]['attending']);
    }

    public function testSupplyRunTalliesPlatesCupsAndDishes(): void
    {
        $this->givenAPublishedParty();

        $payload = $this->getJson('/api/offices/scranton/supply-run');

        self::assertSame(2, $payload['totalGuests']);
        self::assertSame(['Beet salad', 'Cookies'], $payload['runs'][0]['dishes']);
        self::assertSame(4, $payload['runs'][0]['plates']);
        self::assertSame(6, $payload['runs'][0]['cups']);
        self::assertSame(8, $payload['runs'][0]['napkins']);
        self::assertFalse($payload['runs'][0]['crowded']);
    }
}
