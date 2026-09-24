<?php

declare(strict_types=1);

namespace PartyPlanningCommittee;

use PartyPlanningCommittee\Http\CsrfGuard;
use PartyPlanningCommittee\Http\OfficeApiController;
use PartyPlanningCommittee\Http\PartyController;
use PartyPlanningCommittee\Http\Router;
use PartyPlanningCommittee\Http\View;
use PartyPlanningCommittee\Repository\OfficeRepository;
use PartyPlanningCommittee\Repository\PartyRepository;
use PartyPlanningCommittee\Repository\RsvpRepository;
use PDO;

/**
 * Belsnickel assembles the committee. One wiring point, so no impish corner of the
 * application may quietly build its own private router.
 */
final class App
{
    public static function router(PDO $pdo, View $view, CsrfGuard $csrf): Router
    {
        $controller = new PartyController(
            new PartyRepository($pdo),
            new RsvpRepository($pdo),
            new OfficeRepository(),
            $view,
            $csrf
        );

        $api = new OfficeApiController(
            new PartyRepository($pdo),
            new RsvpRepository($pdo),
            new OfficeRepository()
        );

        $router = new Router();
        $router->get('/', static fn (array $params, array $input) => $controller->index());
        $router->get('/calendar', static fn (array $params, array $input) => $controller->calendar());
        $router->get('/upcoming', static fn (array $params, array $input) => $controller->upcoming());
        $router->get('/angela-admin', static fn (array $params, array $input) => $controller->angelaAdmin());
        $router->get('/parties/new', static fn (array $params, array $input) => $controller->newParty());
        $router->get('/parties/{id}', static fn (array $params, array $input) => $controller->show($params));
        $router->get('/parties/{id}/invite', static fn (array $params, array $input) => $controller->invite($params));
        $router->get('/offices/{slug}', static fn (array $params, array $input) => $controller->office($params));
        $router->get('/api/health', static fn (array $params, array $input) => $api->health($params));
        $router->get('/api/offices', static fn (array $params, array $input) => $api->offices($params));
        $router->get('/api/offices/{slug}', static fn (array $params, array $input) => $api->office($params));
        $router->get('/api/offices/{slug}/parties', static fn (array $params, array $input) => $api->parties($params));
        $router->get('/api/offices/{slug}/supply-run', static fn (array $params, array $input) => $api->supplyRun($params));
        $router->post('/parties', static fn (array $params, array $input) => $controller->create($params, $input));
        $router->post('/parties/{id}/rsvps', static fn (array $params, array $input) => $controller->rsvp($params, $input));
        $router->post('/parties/{id}/publish', static fn (array $params, array $input) => $controller->publish($params, $input));
        $router->post('/parties/{id}/cancel', static fn (array $params, array $input) => $controller->cancel($params, $input));

        return $router;
    }

    public static function viewDirectory(): string
    {
        return dirname(__DIR__) . '/views';
    }
}
